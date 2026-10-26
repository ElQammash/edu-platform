<?php

namespace Tests\Feature\Payments;

use App\Payments\Gateways\FakeGateway;
use App\Payments\Gateways\PaymobGateway;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymentGatewayMisconfigured;
use App\Payments\PaymobSignature;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    private function paymobKeys(): void
    {
        config([
            'payments.paymob.secret_key' => 'sk_test',
            'payments.paymob.public_key' => 'pk_test',
            'payments.paymob.hmac_secret' => 'hmac_test',
            'payments.paymob.integration_ids' => [123],
        ]);
    }

    public function test_the_fake_gateway_runs_outside_production(): void
    {
        config(['payments.gateway' => 'fake']);

        $this->assertInstanceOf(FakeGateway::class, app(PaymentGatewayManager::class)->gateway());
    }

    public function test_the_fake_gateway_refuses_production_and_logs_it(): void
    {
        $this->app['env'] = 'production';
        config(['payments.gateway' => 'fake']);
        $this->paymobKeys();
        Log::spy();

        try {
            app(PaymentGatewayManager::class)->gateway();
            $this->fail('The fake gateway must not run in production.');
        } catch (PaymentGatewayMisconfigured $e) {
            $this->assertStringContainsString('disabled in production', $e->getMessage());
        }

        Log::shouldHaveReceived('critical')->once();
    }

    public function test_the_fake_gateway_cannot_be_built_directly_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(PaymentGatewayMisconfigured::class);

        new FakeGateway(new PaymobSignature('x'));
    }

    public function test_paymob_without_keys_fails_loudly_instead_of_falling_back(): void
    {
        $this->app['env'] = 'production';
        config(['payments.gateway' => 'paymob', 'payments.paymob.secret_key' => null]);
        Log::spy();

        $this->expectException(PaymentGatewayMisconfigured::class);
        $this->expectExceptionMessage('PAYMOB_SECRET_KEY');

        app(PaymentGatewayManager::class)->gateway();
    }

    public function test_paymob_with_keys_is_used(): void
    {
        $this->app['env'] = 'production';
        config(['payments.gateway' => 'paymob']);
        $this->paymobKeys();

        $this->assertInstanceOf(PaymobGateway::class, app(PaymentGatewayManager::class)->gateway());
    }

    public function test_production_never_signs_with_the_local_fake_secret(): void
    {
        $this->app['env'] = 'production';
        config(['payments.paymob.hmac_secret' => '']);

        $this->expectException(PaymentGatewayMisconfigured::class);

        app(PaymentGatewayManager::class)->signature();
    }

    public function test_an_unknown_gateway_name_fails(): void
    {
        config(['payments.gateway' => 'stripe']);

        $this->expectException(PaymentGatewayMisconfigured::class);

        app(PaymentGatewayManager::class)->gateway();
    }
}
