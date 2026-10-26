<?php

namespace Tests\Unit;

use App\Payments\GatewayTransaction;
use App\Payments\PaymobSignature;
use PHPUnit\Framework\TestCase;

class PaymobSignatureTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function obj(): array
    {
        return [
            'id' => 192036465,
            'pending' => false,
            'amount_cents' => 75000,
            'success' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'is_refunded' => false,
            'is_3d_secure' => true,
            'integration_id' => 4097558,
            'has_parent_transaction' => false,
            'error_occured' => false,
            'owner' => 302852,
            'currency' => 'EGP',
            'created_at' => '2026-10-27T10:15:30.123456',
            'order' => ['id' => 217503754, 'merchant_order_id' => 'abc-123'],
            'source_data' => ['type' => 'card', 'sub_type' => 'MasterCard', 'pan' => '2346'],
        ];
    }

    public function test_it_signs_the_documented_fields_in_order(): void
    {
        $expected = '75000'.'2026-10-27T10:15:30.123456'.'EGP'.'false'.'false'.'192036465'.'4097558'
            .'true'.'false'.'false'.'false'.'true'.'false'.'217503754'.'302852'.'false'.'2346'
            .'MasterCard'.'card'.'true';

        $this->assertSame(hash_hmac('sha512', $expected, 'secret'), (new PaymobSignature('secret'))->signProcessed($this->obj()));
    }

    public function test_it_verifies_and_rejects(): void
    {
        $signature = new PaymobSignature('secret');
        $hmac = $signature->signProcessed($this->obj());

        $this->assertTrue($signature->verifyProcessed($this->obj(), $hmac));
        $this->assertTrue($signature->verifyProcessed($this->obj(), strtoupper($hmac)));
        $this->assertFalse($signature->verifyProcessed([...$this->obj(), 'amount_cents' => 100], $hmac));
        $this->assertFalse((new PaymobSignature('other'))->verifyProcessed($this->obj(), $hmac));
        $this->assertFalse($signature->verifyProcessed($this->obj(), null));
        $this->assertFalse($signature->verifyProcessed($this->obj(), ''));
    }

    public function test_the_redirect_signature_uses_the_flat_query(): void
    {
        $query = [
            'id' => '192036465', 'pending' => 'false', 'amount_cents' => '75000', 'success' => 'true',
            'is_auth' => 'false', 'is_capture' => 'false', 'is_standalone_payment' => 'true', 'is_voided' => 'false',
            'is_refunded' => 'false', 'is_3d_secure' => 'true', 'integration_id' => '4097558',
            'has_parent_transaction' => 'false', 'error_occured' => 'false', 'owner' => '302852', 'currency' => 'EGP',
            'created_at' => '2026-10-27T10:15:30.123456', 'order' => '217503754', 'source_data.type' => 'card',
            'source_data.sub_type' => 'MasterCard', 'source_data.pan' => '2346',
        ];
        $signature = new PaymobSignature('secret');

        // Same fields, same values: the same signature as the server callback.
        $this->assertSame($signature->signProcessed($this->obj()), $signature->signResponse($query));
    }

    public function test_transactions_are_read_from_the_callback(): void
    {
        $transaction = GatewayTransaction::fromPaymob($this->obj());

        $this->assertSame('192036465', $transaction->id);
        $this->assertSame('217503754', $transaction->orderId);
        $this->assertSame('abc-123', $transaction->merchantOrderId);
        $this->assertSame(75000, $transaction->amountCents);
        $this->assertTrue($transaction->success);
        $this->assertFalse($transaction->isRefund);
        $this->assertSame('card •••• 2346', $transaction->method);
    }
}
