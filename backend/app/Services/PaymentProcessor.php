<?php

namespace App\Services;

use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Notifications\PaymentConfirmed;
use App\Notifications\PaymentRefunded;
use App\Payments\GatewayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * What a verified gateway transaction means for a payment. Every message
 * is applied once (payment_events), under a row lock, so callbacks,
 * redirects and inquiries can arrive in any order and any number of times.
 *
 * - a successful payment for the full amount enrolls the student;
 * - a refund records the amount: a partial refund keeps the course,
 *   a full refund (or a void) removes the access the payment gave;
 * - an amount or currency mismatch is recorded as rejected and changes nothing.
 */
class PaymentProcessor
{
    public function __construct(private readonly LessonProgressService $progress) {}

    public function apply(GatewayTransaction $transaction, string $source): ?Payment
    {
        $found = $this->find($transaction);

        if ($found === null) {
            Log::warning('Payment message for an unknown order', [
                'transaction' => $transaction->id,
                'order' => $transaction->orderId,
                'merchant_order' => $transaction->merchantOrderId,
            ]);

            return null;
        }

        return DB::transaction(function () use ($found, $transaction, $source): Payment {
            /** @var Payment $payment */
            $payment = Payment::query()->lockForUpdate()->findOrFail($found->id);
            $type = $this->typeOf($transaction);

            if ($type === PaymentEvent::TYPE_PAYMENT && $this->alreadyRecorded($payment, $transaction->id, $type)) {
                // A later update of the same payment can carry the refunded total.
                $this->applyRefundedTotal($payment, $transaction, $source);

                return $payment;
            }

            if ($this->alreadyRecorded($payment, $transaction->id, $type)) {
                return $payment;
            }

            if (! $this->matches($payment, $transaction, $type)) {
                $this->record($payment, $transaction, PaymentEvent::TYPE_REJECTED, $source);
                Log::warning('Payment message rejected: amount or currency mismatch', [
                    'payment' => $payment->reference,
                    'expected' => [$payment->amountCents(), $payment->currency],
                    'received' => [$transaction->amountCents, $transaction->currency],
                ]);

                return $payment;
            }

            $this->record($payment, $transaction, $type, $source);

            match ($type) {
                PaymentEvent::TYPE_PAYMENT => $this->markPaid($payment, $transaction),
                PaymentEvent::TYPE_FAILED => $this->markFailed($payment, $transaction),
                PaymentEvent::TYPE_REFUND => $this->refund($payment, $transaction->amountCents / 100),
                PaymentEvent::TYPE_VOID => $this->refund($payment, $payment->netAmount()),
                default => null, // pending: nothing to do yet
            };

            return $payment;
        });
    }

    private function find(GatewayTransaction $transaction): ?Payment
    {
        if ($transaction->merchantOrderId !== null) {
            $payment = Payment::query()->where('reference', $transaction->merchantOrderId)->first();
            if ($payment !== null) {
                return $payment;
            }
        }

        return $transaction->orderId !== null
            ? Payment::query()->where('gateway_order_id', $transaction->orderId)->first()
            : null;
    }

    private function typeOf(GatewayTransaction $transaction): string
    {
        if ($transaction->isRefund || $transaction->isVoid) {
            // A refund or void that did not go through changes nothing.
            if (! $transaction->success) {
                return PaymentEvent::TYPE_FAILED.'_'.($transaction->isVoid ? PaymentEvent::TYPE_VOID : PaymentEvent::TYPE_REFUND);
            }

            return $transaction->isVoid ? PaymentEvent::TYPE_VOID : PaymentEvent::TYPE_REFUND;
        }

        return match (true) {
            $transaction->pending => PaymentEvent::TYPE_PENDING,
            $transaction->success => PaymentEvent::TYPE_PAYMENT,
            default => PaymentEvent::TYPE_FAILED,
        };
    }

    private function matches(Payment $payment, GatewayTransaction $transaction, string $type): bool
    {
        if (strtoupper($transaction->currency) !== strtoupper($payment->currency)) {
            return false;
        }

        return match ($type) {
            PaymentEvent::TYPE_PAYMENT, PaymentEvent::TYPE_PENDING => $transaction->amountCents === $payment->amountCents(),
            PaymentEvent::TYPE_REFUND => $transaction->amountCents > 0
                && $transaction->amountCents <= (int) round($payment->netAmount() * 100),
            default => true,
        };
    }

    private function markPaid(Payment $payment, GatewayTransaction $transaction): void
    {
        if ($payment->status->grantsAccess() || $payment->status === PaymentState::Refunded) {
            return;
        }

        // Also after "expired" or "failed": the money was taken, so the student gets the course.
        $payment->update([
            'status' => PaymentState::Paid,
            'paid_at' => now(),
            'gateway_transaction_id' => $transaction->id,
            'payment_method' => $transaction->method,
        ]);

        $enrollment = Enrollment::query()->firstOrCreate(
            ['student_id' => $payment->student_id, 'course_id' => $payment->course_id],
            ['payment_status' => PaymentStatus::Paid, 'payment_id' => $payment->id, 'enrolled_at' => now(), 'progress_percent' => 0],
        );

        if (! $enrollment->wasRecentlyCreated) {
            // Enrolled meanwhile (e.g. manually by the admin): keep that enrollment; the admin can refund.
            Log::warning('Online payment for a student who is already enrolled', ['payment' => $payment->reference]);
            $enrollment->payment_id ??= $payment->id;
            $enrollment->save();
        }

        // Progress from an earlier enrollment of the same course still counts.
        $this->progress->refreshCourseProgress($payment->student, $payment->course);

        $payment->student->notify(new PaymentConfirmed($payment));
    }

    private function markFailed(Payment $payment, GatewayTransaction $transaction): void
    {
        if ($payment->status !== PaymentState::Pending && $payment->status !== PaymentState::Expired) {
            return;
        }

        $payment->update(['status' => PaymentState::Failed, 'failed_at' => now(), 'payment_method' => $transaction->method]);
    }

    /**
     * Record a refund. Only a full refund removes the course access.
     */
    private function refund(Payment $payment, float $amount): void
    {
        if (! $payment->status->grantsAccess()) {
            Log::warning('Refund for a payment that is not paid', ['payment' => $payment->reference, 'status' => $payment->status->value]);

            return;
        }

        $refunded = min((float) $payment->amount, round((float) $payment->refunded_amount + $amount, 2));
        $full = $refunded >= (float) $payment->amount;

        $payment->update([
            'refunded_amount' => $refunded,
            'status' => $full ? PaymentState::Refunded : PaymentState::PartiallyRefunded,
            'refunded_at' => now(),
        ]);

        if ($full) {
            // Only the access this payment gave; a manual enrollment stays.
            Enrollment::query()
                ->where('payment_id', $payment->id)
                ->where('payment_status', PaymentStatus::Paid)
                ->delete();
        }

        $payment->student->notify(new PaymentRefunded($payment, $amount, $full));
    }

    /**
     * Paymob may report refunds as an update of the original transaction
     * ("refunded_amount_cents") instead of a separate refund transaction.
     */
    private function applyRefundedTotal(Payment $payment, GatewayTransaction $transaction, string $source): void
    {
        $reportedCents = (int) ($transaction->payload['refunded_amount_cents'] ?? 0);
        $knownCents = (int) round((float) $payment->refunded_amount * 100);

        if ($reportedCents <= $knownCents) {
            return;
        }

        $eventId = $transaction->id.':refunded:'.$reportedCents;
        if ($this->alreadyRecorded($payment, $eventId, PaymentEvent::TYPE_REFUND)) {
            return;
        }

        $payment->events()->create([
            'gateway_transaction_id' => $eventId,
            'type' => PaymentEvent::TYPE_REFUND,
            'amount' => ($reportedCents - $knownCents) / 100,
            'source' => $source,
            'payload' => $transaction->payload,
            'created_at' => now(),
        ]);

        $this->refund($payment, ($reportedCents - $knownCents) / 100);
    }

    private function alreadyRecorded(Payment $payment, string $transactionId, string $type): bool
    {
        return $payment->events()->where('gateway_transaction_id', $transactionId)->where('type', $type)->exists();
    }

    private function record(Payment $payment, GatewayTransaction $transaction, string $type, string $source): void
    {
        $payment->events()->create([
            'gateway_transaction_id' => $transaction->id,
            'type' => $type,
            'amount' => $transaction->amountCents / 100,
            'source' => $source,
            'payload' => $transaction->payload,
            'created_at' => now(),
        ]);
    }
}
