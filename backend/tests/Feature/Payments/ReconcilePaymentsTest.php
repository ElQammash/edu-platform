<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentState;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReconcilePaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.paymob.secret_key' => 'sk', 'payments.paymob.public_key' => 'pk',
            'payments.paymob.hmac_secret' => 'h', 'payments.paymob.integration_ids' => [1],
            'payments.paymob.api_key' => 'api_key_test',
        ]);
        Notification::fake();
    }

    private function pending(int $minutesAgo, string $orderId): Payment
    {
        $payment = Payment::factory()->create(['amount' => 500, 'gateway_order_id' => $orderId]);
        $payment->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();

        return $payment;
    }

    public function test_a_payment_whose_callback_was_lost_is_settled_by_inquiry(): void
    {
        $lost = $this->pending(20, '700');
        $recent = $this->pending(5, '701');

        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'tok']),
            'accept.paymob.com/api/ecommerce/orders/transaction_inquiry' => Http::response([
                'id' => 8800, 'success' => true, 'pending' => false, 'amount_cents' => 50000, 'currency' => 'EGP',
                'is_refund' => false, 'is_void' => false,
                'order' => ['id' => 700, 'merchant_order_id' => $lost->reference],
                'source_data' => ['type' => 'wallet', 'pan' => '01012345678', 'sub_type' => 'wallet'],
            ]),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(PaymentState::Paid, $lost->fresh()?->status);
        $this->assertSame('wallet', $lost->fresh()?->payment_method);
        $this->assertSame(PaymentEvent::SOURCE_INQUIRY, $lost->events()->sole()->source);
        $this->assertTrue($lost->student->isEnrolledIn($lost->course));
        // Too recent: the callback may still come.
        $this->assertSame(PaymentState::Pending, $recent->fresh()?->status);
        Http::assertSentCount(2);
    }

    public function test_an_unreachable_gateway_is_logged_and_skipped(): void
    {
        $payment = $this->pending(30, '702');
        Http::fake(['*' => Http::response('down', 503)]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(PaymentState::Pending, $payment->fresh()?->status);
    }

    public function test_payments_pending_for_a_day_expire(): void
    {
        $stale = $this->pending(25 * 60, '703');
        $fake = Payment::factory()->create(['gateway' => 'fake']);
        $fake->forceFill(['created_at' => now()->subDays(2)])->save();
        Http::fake();

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(PaymentState::Expired, $stale->fresh()?->status);
        $this->assertSame(PaymentState::Expired, $fake->fresh()?->status);
        Http::assertNothingSent();
    }

    public function test_it_is_scheduled_before_the_queue_worker(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn (Event $event): string => (string) $event->command)->values();

        $reconcile = $commands->search(fn (string $c): bool => str_contains($c, 'payments:reconcile'));
        $worker = $commands->search(fn (string $c): bool => str_contains($c, 'queue:work --stop-when-empty'));
        $this->assertIsInt($reconcile);
        $this->assertIsInt($worker);
        $this->assertLessThan($worker, $reconcile);
    }
}
