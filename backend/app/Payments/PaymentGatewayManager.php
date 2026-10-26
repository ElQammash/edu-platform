<?php

namespace App\Payments;

use App\Payments\Gateways\FakeGateway;
use App\Payments\Gateways\PaymobGateway;
use Illuminate\Support\Facades\Log;

/**
 * Picks the configured gateway and refuses unsafe setups loudly: in
 * production the fake gateway is never used, whatever PAYMENT_GATEWAY says,
 * and missing Paymob keys stop the payment instead of falling back.
 */
class PaymentGatewayManager
{
    public const PAYMOB = 'paymob';

    public const FAKE = 'fake';

    public function gateway(): PaymentGateway
    {
        return match ($this->configured()) {
            self::FAKE => $this->fake(),
            self::PAYMOB => $this->paymob(),
            default => $this->fail('Unknown PAYMENT_GATEWAY "'.$this->configured().'"; use "paymob" or "fake".'),
        };
    }

    /**
     * The gateway a stored payment was made with (a payment keeps its gateway
     * if the setting changes later).
     */
    public function forName(string $name): PaymentGateway
    {
        return match ($name) {
            self::FAKE => $this->fake(),
            self::PAYMOB => $this->paymob(),
            default => $this->fail("Unknown payment gateway \"{$name}\"."),
        };
    }

    public function fake(): FakeGateway
    {
        self::assertFakeAllowed();

        return new FakeGateway($this->signature());
    }

    /**
     * The secret that signs Paymob callbacks. The fake gateway signs its
     * callbacks with a fixed local secret when no Paymob secret is set.
     */
    public function signature(): PaymobSignature
    {
        $secret = (string) config('payments.paymob.hmac_secret');

        if ($secret === '') {
            if (app()->isProduction()) {
                $this->fail('PAYMOB_HMAC_SECRET is not set: Paymob callbacks cannot be verified.');
            }
            $secret = (string) config('payments.fake.hmac_secret');
        }

        return new PaymobSignature($secret);
    }

    /**
     * The fake gateway and its checkout page never work in production.
     *
     * @throws PaymentGatewayMisconfigured
     */
    public static function assertFakeAllowed(): void
    {
        if (app()->isProduction()) {
            $message = 'The fake payment gateway is disabled in production. Set PAYMENT_GATEWAY=paymob and the Paymob keys.';
            Log::critical($message);

            throw new PaymentGatewayMisconfigured($message);
        }
    }

    private function paymob(): PaymobGateway
    {
        $config = (array) config('payments.paymob');

        foreach (['secret_key', 'public_key', 'hmac_secret'] as $key) {
            if (blank($config[$key] ?? null)) {
                $this->fail('Paymob is not configured: PAYMOB_'.strtoupper($key).' is missing.');
            }
        }

        if (($config['integration_ids'] ?? []) === []) {
            $this->fail('Paymob is not configured: PAYMOB_INTEGRATION_IDS is empty.');
        }

        return new PaymobGateway($config);
    }

    private function configured(): string
    {
        return (string) config('payments.gateway');
    }

    /**
     * @throws PaymentGatewayMisconfigured
     */
    private function fail(string $message): never
    {
        Log::critical($message);

        throw new PaymentGatewayMisconfigured($message);
    }
}
