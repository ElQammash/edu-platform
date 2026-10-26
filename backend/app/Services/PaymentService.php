<?php

namespace App\Services;

use App\Enums\PaymentState;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Online payments: starting a checkout here, and what gateway messages mean
 * for a payment and its enrollment (PaymentProcessor). Callers authorize.
 */
class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways) {}

    /**
     * The student's checkout for the course: their pending one if it is
     * still open, otherwise a new one at today's price.
     *
     * @throws HttpException 502 when the gateway can't be reached
     */
    public function checkout(User $student, Course $course): Payment
    {
        $open = Payment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', PaymentState::Pending)
            ->latest('id')
            ->first();

        if ($open?->isReusable()) {
            return $open;
        }

        // Resolve (and validate) the gateway before anything is stored.
        $gateway = $this->gateways->gateway();

        $payment = Payment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'currency' => (string) config('payments.currency'),
            'gateway' => $gateway->name(),
            'status' => PaymentState::Pending,
            'expires_at' => now()->addMinutes(Payment::VALID_MINUTES),
        ]);
        $payment->setRelation('student', $student)->setRelation('course', $course);

        try {
            $session = $gateway->createCheckout($payment);
        } catch (ConnectionException|RequestException $e) {
            Log::error('Payment checkout failed', ['payment' => $payment->reference, 'error' => $e->getMessage()]);
            $payment->update(['status' => PaymentState::Failed, 'failed_at' => now()]);

            throw new HttpException(502, __('payments.errors.gateway_unavailable'), $e);
        }

        $payment->update([
            'checkout_url' => $session->url,
            'gateway_order_id' => $session->gatewayOrderId,
        ]);

        return $payment;
    }
}
