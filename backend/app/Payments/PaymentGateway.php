<?php

namespace App\Payments;

use App\Models\Payment;

/**
 * A payment provider. The gateway only starts checkouts and answers
 * questions; what a transaction means for the payment is PaymentService's job.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Open a checkout for the payment and say where to send the student.
     */
    public function createCheckout(Payment $payment): CheckoutSession;

    /**
     * The gateway's latest transaction for the payment, or null if there is
     * none yet (used when a callback never arrived).
     */
    public function inquire(Payment $payment): ?GatewayTransaction;
}
