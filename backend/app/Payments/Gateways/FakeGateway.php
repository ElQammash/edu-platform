<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\GatewayTransaction;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymobSignature;
use Illuminate\Support\Str;

/**
 * A local stand-in for Paymob: its checkout page (Pay / Fail buttons) sends
 * a Paymob-shaped, correctly signed callback to the real callback route, so
 * the whole flow runs without a Paymob account or a public URL.
 * Refuses to run in production (PaymentGatewayManager::assertFakeAllowed).
 */
class FakeGateway implements PaymentGateway
{
    public function __construct(private readonly PaymobSignature $signature)
    {
        PaymentGatewayManager::assertFakeAllowed();
    }

    public function name(): string
    {
        return PaymentGatewayManager::FAKE;
    }

    public function createCheckout(Payment $payment): CheckoutSession
    {
        return new CheckoutSession(
            url: route('payments.fake-checkout', $payment),
            gatewayOrderId: 'fake-'.$payment->id,
        );
    }

    public function inquire(Payment $payment): ?GatewayTransaction
    {
        return null;
    }

    /**
     * The callback body Paymob would send, and its signature.
     *
     * @return array{payload: array<string, mixed>, hmac: string}
     */
    public function callbackFor(Payment $payment, bool $success): array
    {
        $obj = [
            'id' => (int) (crc32($payment->reference.($success ? 'ok' : 'fail')) % 90000000) + 10000000,
            'pending' => false,
            'amount_cents' => $payment->amountCents(),
            'success' => $success,
            'is_auth' => false,
            'is_capture' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'is_refunded' => false,
            'is_3d_secure' => true,
            'integration_id' => 0,
            'has_parent_transaction' => false,
            'error_occured' => false,
            'is_refund' => false,
            'is_void' => false,
            'owner' => 0,
            'currency' => $payment->currency,
            'created_at' => now()->toIso8601String(),
            'order' => ['id' => $payment->gateway_order_id, 'merchant_order_id' => $payment->reference],
            'source_data' => ['type' => 'card', 'sub_type' => 'MasterCard', 'pan' => '2346'],
            'data' => ['message' => $success ? 'Approved' : 'Declined', 'fake' => true, 'nonce' => Str::random(8)],
        ];

        return ['payload' => ['type' => 'TRANSACTION', 'obj' => $obj], 'hmac' => $this->signature->signProcessed($obj)];
    }
}
