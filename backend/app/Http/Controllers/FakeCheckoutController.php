<?php

namespace App\Http\Controllers;

use App\Enums\PaymentState;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use Illuminate\Contracts\View\View;

/**
 * The fake gateway's checkout page, for local development only. Its
 * buttons send the same signed callback Paymob would, to the real callback
 * route, then go to the React result page like Paymob's redirect does.
 */
class FakeCheckoutController extends Controller
{
    public function __invoke(Payment $payment, PaymentGatewayManager $gateways): View
    {
        // Throws (and logs) in production, before anything is shown.
        $gateway = $gateways->fake();

        abort_unless($payment->gateway === PaymentGatewayManager::FAKE, 404);
        $payment->load(['course', 'student']);

        $frontend = rtrim((string) config('app.frontend_url'), '/');

        return view('payments.fake-checkout', [
            'payment' => $payment,
            'open' => $payment->status === PaymentState::Pending,
            'pay' => $gateway->callbackFor($payment, success: true),
            'fail' => $gateway->callbackFor($payment, success: false),
            'callbackUrl' => route('api.v1.payments.paymob.callback'),
            'resultUrl' => $frontend.'/payments/'.$payment->reference,
        ]);
    }
}
