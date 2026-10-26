<?php

namespace App\Console\Commands;

use App\Enums\PaymentState;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentProcessor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Catches payments whose callback never arrived: asks the gateway about
 * checkouts still pending after 15 minutes, and expires the ones still
 * pending after a day. Runs every 10 minutes from the scheduler.
 */
#[Signature('payments:reconcile')]
#[Description('Ask the gateway about pending payments and expire stale ones')]
class ReconcilePayments extends Command
{
    /** Give the callback time to arrive first. */
    public const ASK_AFTER_MINUTES = 15;

    public const EXPIRE_AFTER_HOURS = 24;

    public function handle(PaymentGatewayManager $gateways, PaymentProcessor $processor): int
    {
        $settled = 0;

        $pending = Payment::query()
            ->where('status', PaymentState::Pending)
            ->where('gateway', PaymentGatewayManager::PAYMOB)
            ->where('created_at', '<=', now()->subMinutes(self::ASK_AFTER_MINUTES))
            ->where('created_at', '>', now()->subHours(self::EXPIRE_AFTER_HOURS))
            ->get();

        foreach ($pending as $payment) {
            try {
                $transaction = $gateways->forName($payment->gateway)->inquire($payment);

                if ($transaction !== null) {
                    $processor->apply($transaction, PaymentEvent::SOURCE_INQUIRY);
                    $settled += (int) ($payment->fresh()?->status !== PaymentState::Pending);
                }
            } catch (Throwable $e) {
                // One unreachable payment must not stop the others.
                Log::error('Payment reconciliation failed', ['payment' => $payment->reference, 'error' => $e->getMessage()]);
            }
        }

        $expired = Payment::query()
            ->where('status', PaymentState::Pending)
            ->where('created_at', '<=', now()->subHours(self::EXPIRE_AFTER_HOURS))
            ->update(['status' => PaymentState::Expired, 'updated_at' => now()]);

        $this->info("Settled {$settled} and expired {$expired} payment(s).");

        return self::SUCCESS;
    }
}
