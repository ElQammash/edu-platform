<?php

namespace Database\Seeders;

use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Online payments in each outcome, with the gateway messages that would
 * have produced them (fake gateway):
 * - student4 paid for the olympiad course and got 250 EGP back (partial
 *   refund): still enrolled;
 * - student5 paid for calculus and got everything back: not enrolled;
 * - student2's card was declined on the olympiad course.
 * Written directly, so no receipt emails are queued. Skipped when payments exist.
 */
class DemoPaymentSeeder extends Seeder
{
    public function run(): void
    {
        if (Payment::exists()) {
            return;
        }

        $olympiad = Course::where('slug', 'physics-olympiad-preparation')->firstOrFail();
        $calculus = Course::where('slug', 'calculus-secondary-3')->firstOrFail();
        $student = fn (int $n): User => User::where('email', "student{$n}@edu-platform.test")->firstOrFail();

        $partial = $this->payment($student(4), $olympiad, PaymentState::PartiallyRefunded, daysAgo: 6, refunded: 250);
        Enrollment::firstOrCreate(
            ['student_id' => $partial->student_id, 'course_id' => $olympiad->id],
            ['payment_status' => PaymentStatus::Paid, 'payment_id' => $partial->id, 'enrolled_at' => $partial->paid_at, 'progress_percent' => 0],
        );

        $this->payment($student(5), $calculus, PaymentState::Refunded, daysAgo: 10, refunded: (float) $calculus->price);

        $this->payment($student(2), $olympiad, PaymentState::Failed, daysAgo: 2);
    }

    private function payment(User $student, Course $course, PaymentState $state, int $daysAgo, float $refunded = 0): Payment
    {
        $at = now()->subDays($daysAgo)->setTime(18, 30);
        $paid = $state !== PaymentState::Failed;
        $transactionId = (string) random_int(10_000_000, 99_999_999);

        $payment = Payment::create([
            // Model events are off while seeding, so the model can't make one.
            'reference' => (string) Str::uuid(),
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'refunded_amount' => $refunded,
            'currency' => 'EGP',
            'gateway' => 'fake',
            'status' => $state,
            'gateway_order_id' => 'fake-demo-'.$transactionId,
            'gateway_transaction_id' => $paid ? $transactionId : null,
            'payment_method' => $paid ? 'card •••• 2346' : 'card •••• 0008',
            'expires_at' => $at->addHour(),
            'paid_at' => $paid ? $at->addMinutes(2) : null,
            'failed_at' => $paid ? null : $at->addMinutes(2),
            'refunded_at' => $refunded > 0 ? $at->addDays(2) : null,
        ]);
        $payment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        $this->event($payment, $transactionId, $paid ? PaymentEvent::TYPE_PAYMENT : PaymentEvent::TYPE_FAILED, (float) $course->price, $at->addMinutes(2));
        if ($refunded > 0) {
            $this->event($payment, (string) ((int) $transactionId + 1), PaymentEvent::TYPE_REFUND, $refunded, $at->addDays(2));
        }

        return $payment;
    }

    private function event(Payment $payment, string $transactionId, string $type, float $amount, mixed $at): void
    {
        $payment->events()->create([
            'gateway_transaction_id' => $transactionId,
            'type' => $type,
            'amount' => $amount,
            'source' => PaymentEvent::SOURCE_CALLBACK,
            'payload' => [
                'id' => (int) $transactionId,
                'amount_cents' => (int) round($amount * 100),
                'success' => $type !== PaymentEvent::TYPE_FAILED,
                'is_refund' => $type === PaymentEvent::TYPE_REFUND,
                'currency' => 'EGP',
                'order' => ['id' => $payment->gateway_order_id, 'merchant_order_id' => $payment->reference],
                'data' => ['message' => $type === PaymentEvent::TYPE_FAILED ? 'Declined' : 'Approved', 'demo' => true],
            ],
            'created_at' => $at,
        ]);
    }
}
