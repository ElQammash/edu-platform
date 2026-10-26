<?php

namespace Tests\Feature\Payments;

use App\Enums\CourseStatus;
use App\Enums\PaymentState;
use App\Models\Course;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FakeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function fakePayment(): Payment
    {
        $course = Course::factory()->create(['status' => CourseStatus::Published, 'price' => 300]);

        return Payment::factory()->create(['course_id' => $course->id, 'amount' => 300, 'gateway' => 'fake', 'gateway_order_id' => 'fake-1']);
    }

    public function test_the_page_shows_pay_and_fail_buttons_locally(): void
    {
        $payment = $this->fakePayment();

        $this->get(route('payments.fake-checkout', $payment))
            ->assertOk()
            ->assertSee(__('payments.fake.notice'))
            ->assertSee('data-result="pay"', false)
            ->assertSee('payments\/paymob\/callback', false);
    }

    public function test_its_signed_callback_pays_through_the_real_callback_route(): void
    {
        $payment = $this->fakePayment();
        ['payload' => $payload, 'hmac' => $hmac] = app(PaymentGatewayManager::class)->fake()->callbackFor($payment, success: true);

        $this->postJson(route('api.v1.payments.paymob.callback', ['hmac' => $hmac]), $payload)->assertOk();

        $this->assertSame(PaymentState::Paid, $payment->fresh()?->status);
        $this->assertTrue($payment->student->isEnrolledIn($payment->course));
    }

    public function test_the_page_refuses_to_work_in_production_even_if_the_setting_says_fake(): void
    {
        $payment = $this->fakePayment();
        $this->app['env'] = 'production';
        config(['payments.gateway' => 'fake']);
        Log::spy();

        $this->get(route('payments.fake-checkout', $payment))->assertServerError();

        Log::shouldHaveReceived('critical')->withArgs(fn (string $message): bool => str_contains($message, 'disabled in production'));
        $this->assertSame(PaymentState::Pending, $payment->fresh()?->status);
    }

    public function test_other_gateways_payments_are_not_shown(): void
    {
        $payment = Payment::factory()->create(['gateway' => 'paymob']);

        $this->get(route('payments.fake-checkout', $payment))->assertNotFound();
    }
}
