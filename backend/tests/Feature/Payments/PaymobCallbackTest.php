<?php

namespace Tests\Feature\Payments;

use App\Enums\CourseStatus;
use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Notifications\PaymentConfirmed;
use App\Notifications\PaymentRefunded;
use App\Payments\PaymobSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymobCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-hmac-secret';

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.paymob.hmac_secret' => self::SECRET]);
        Notification::fake();

        $course = Course::factory()->create(['status' => CourseStatus::Published, 'price' => 750]);
        $this->payment = Payment::factory()->create([
            'course_id' => $course->id,
            'amount' => 750,
            'gateway_order_id' => '555001',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function obj(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 9001,
            'pending' => false,
            'amount_cents' => 75000,
            'success' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'is_refunded' => false,
            'is_3d_secure' => true,
            'integration_id' => 111,
            'has_parent_transaction' => false,
            'error_occured' => false,
            'is_refund' => false,
            'is_void' => false,
            'owner' => 1,
            'currency' => 'EGP',
            'created_at' => '2026-10-27T10:00:00.000000',
            'order' => ['id' => 555001, 'merchant_order_id' => $this->payment->reference],
            'source_data' => ['type' => 'card', 'sub_type' => 'MasterCard', 'pan' => '2346'],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $obj
     */
    private function send(array $obj, ?string $hmac = null): TestResponse
    {
        $hmac ??= (new PaymobSignature(self::SECRET))->signProcessed($obj);

        return $this->postJson(route('api.v1.payments.paymob.callback', ['hmac' => $hmac]), ['type' => 'TRANSACTION', 'obj' => $obj]);
    }

    private function enrollment(): ?Enrollment
    {
        return Enrollment::query()->where('student_id', $this->payment->student_id)->where('course_id', $this->payment->course_id)->first();
    }

    public function test_a_verified_successful_payment_enrolls_the_student_once(): void
    {
        $this->send($this->obj())->assertOk();
        $this->send($this->obj())->assertOk();

        $payment = $this->payment->fresh();
        $this->assertSame(PaymentState::Paid, $payment?->status);
        $this->assertSame('9001', $payment->gateway_transaction_id);
        $this->assertSame('card •••• 2346', $payment->payment_method);
        $this->assertSame(PaymentStatus::Paid, $this->enrollment()?->payment_status);
        $this->assertSame($payment->id, $this->enrollment()->payment_id);
        $this->assertSame(1, PaymentEvent::count());
        Notification::assertSentToTimes($payment->student, PaymentConfirmed::class, 1);
    }

    public function test_an_invalid_or_missing_hmac_is_refused(): void
    {
        $this->send($this->obj(), hmac: str_repeat('a', 128))->assertForbidden();
        $this->postJson(route('api.v1.payments.paymob.callback'), ['type' => 'TRANSACTION', 'obj' => $this->obj()])->assertForbidden();

        // A tampered amount breaks the signature.
        $hmac = (new PaymobSignature(self::SECRET))->signProcessed($this->obj());
        $this->send($this->obj(['amount_cents' => 100]), $hmac)->assertForbidden();

        $this->assertSame(PaymentState::Pending, $this->payment->fresh()?->status);
        $this->assertNull($this->enrollment());
    }

    public function test_a_signed_amount_or_currency_mismatch_is_recorded_and_ignored(): void
    {
        $this->send($this->obj(['amount_cents' => 100]))->assertOk();
        $this->send($this->obj(['id' => 9002, 'currency' => 'USD']))->assertOk();

        $this->assertSame(PaymentState::Pending, $this->payment->fresh()?->status);
        $this->assertNull($this->enrollment());
        $this->assertSame(2, PaymentEvent::where('type', PaymentEvent::TYPE_REJECTED)->count());
    }

    public function test_a_declined_payment_is_marked_failed_and_a_pending_one_waits(): void
    {
        $this->send($this->obj(['pending' => true, 'success' => false]))->assertOk();
        $this->assertSame(PaymentState::Pending, $this->payment->fresh()?->status);

        $this->send($this->obj(['id' => 9002, 'success' => false]))->assertOk();
        $this->assertSame(PaymentState::Failed, $this->payment->fresh()?->status);
        $this->assertNull($this->enrollment());
    }

    public function test_a_late_success_after_expiry_still_enrolls(): void
    {
        $this->payment->update(['status' => PaymentState::Expired]);

        $this->send($this->obj())->assertOk();

        $this->assertSame(PaymentState::Paid, $this->payment->fresh()?->status);
        $this->assertNotNull($this->enrollment());
    }

    public function test_the_order_id_finds_the_payment_without_a_merchant_reference(): void
    {
        $this->send($this->obj(['order' => ['merchant_order_id' => null]]))->assertOk();

        $this->assertSame(PaymentState::Paid, $this->payment->fresh()?->status);
    }

    public function test_a_partial_refund_is_recorded_and_the_student_keeps_access(): void
    {
        $this->send($this->obj())->assertOk();

        $this->send($this->obj(['id' => 9100, 'amount_cents' => 25000, 'is_refund' => true, 'has_parent_transaction' => true]))->assertOk();

        $payment = $this->payment->fresh();
        $this->assertSame(PaymentState::PartiallyRefunded, $payment?->status);
        $this->assertSame('250.00', $payment->refunded_amount);
        $this->assertSame(500.0, $payment->netAmount());
        $this->assertNotNull($this->enrollment());
        Notification::assertSentTo($payment->student, PaymentRefunded::class, fn (PaymentRefunded $n): bool => ! $n->full && $n->amount === 250.0);
    }

    public function test_refunds_adding_up_to_the_full_amount_remove_access(): void
    {
        $this->send($this->obj())->assertOk();
        $this->send($this->obj(['id' => 9100, 'amount_cents' => 25000, 'is_refund' => true]))->assertOk();
        $this->send($this->obj(['id' => 9101, 'amount_cents' => 50000, 'is_refund' => true]))->assertOk();
        // The same refund again changes nothing.
        $this->send($this->obj(['id' => 9101, 'amount_cents' => 50000, 'is_refund' => true]))->assertOk();

        $payment = $this->payment->fresh();
        $this->assertSame(PaymentState::Refunded, $payment?->status);
        $this->assertSame('750.00', $payment->refunded_amount);
        $this->assertNull($this->enrollment());
        Notification::assertSentTo($payment->student, PaymentRefunded::class, fn (PaymentRefunded $n): bool => $n->full);
    }

    public function test_a_refund_larger_than_what_is_left_is_rejected(): void
    {
        $this->send($this->obj())->assertOk();
        $this->send($this->obj(['id' => 9100, 'amount_cents' => 80000, 'is_refund' => true]))->assertOk();

        $this->assertSame(PaymentState::Paid, $this->payment->fresh()?->status);
        $this->assertSame(1, PaymentEvent::where('type', PaymentEvent::TYPE_REJECTED)->count());
    }

    public function test_a_void_counts_as_a_full_refund(): void
    {
        $this->send($this->obj())->assertOk();
        $this->send($this->obj(['id' => 9200, 'is_void' => true]))->assertOk();

        $this->assertSame(PaymentState::Refunded, $this->payment->fresh()?->status);
        $this->assertNull($this->enrollment());
    }

    public function test_a_failed_refund_changes_nothing(): void
    {
        $this->send($this->obj())->assertOk();
        $this->send($this->obj(['id' => 9100, 'amount_cents' => 25000, 'is_refund' => true, 'success' => false]))->assertOk();

        $this->assertSame(PaymentState::Paid, $this->payment->fresh()?->status);
        $this->assertSame('0.00', $this->payment->fresh()?->refunded_amount);
    }

    public function test_a_refunded_total_on_the_original_transaction_is_applied(): void
    {
        $this->send($this->obj())->assertOk();
        $this->send($this->obj(['is_refunded' => true, 'refunded_amount_cents' => 75000]))->assertOk();

        $this->assertSame(PaymentState::Refunded, $this->payment->fresh()?->status);
        $this->assertNull($this->enrollment());
    }

    public function test_a_full_refund_leaves_a_manual_enrollment_alone(): void
    {
        Enrollment::factory()->create([
            'student_id' => $this->payment->student_id,
            'course_id' => $this->payment->course_id,
            'payment_status' => PaymentStatus::Manual,
        ]);

        $this->send($this->obj())->assertOk();
        $this->send($this->obj(['id' => 9200, 'is_void' => true]))->assertOk();

        $this->assertSame(PaymentStatus::Manual, $this->enrollment()?->payment_status);
    }

    public function test_unknown_orders_and_other_callback_types_are_acknowledged(): void
    {
        $this->send($this->obj(['order' => ['id' => 1, 'merchant_order_id' => 'nope']]))->assertOk();
        $this->postJson(route('api.v1.payments.paymob.callback'), ['type' => 'TOKEN', 'obj' => ['token' => 'x']])->assertOk();
        $this->postJson(route('api.v1.payments.paymob.callback'), ['hello' => 'world'])->assertStatus(400);

        $this->assertSame(PaymentState::Pending, $this->payment->fresh()?->status);
    }

    public function test_the_browser_redirect_is_verified_applied_and_sent_to_the_result_page(): void
    {
        $query = [
            'id' => '9001', 'pending' => 'false', 'amount_cents' => '75000', 'success' => 'true',
            'is_auth' => 'false', 'is_capture' => 'false', 'is_standalone_payment' => 'true', 'is_voided' => 'false',
            'is_refunded' => 'false', 'is_3d_secure' => 'true', 'integration_id' => '111',
            'has_parent_transaction' => 'false', 'error_occured' => 'false', 'owner' => '1', 'currency' => 'EGP',
            'created_at' => '2026-10-27T10:00:00.000000', 'order' => '555001', 'merchant_order_id' => $this->payment->reference,
            'source_data.type' => 'card', 'source_data.sub_type' => 'MasterCard', 'source_data.pan' => '2346',
        ];
        $query['hmac'] = (new PaymobSignature(self::SECRET))->signResponse($query);

        // Built by hand: the dots in "source_data.pan" must stay as sent.
        $this->get('/payments/paymob/return?'.implode('&', array_map(fn ($k, $v) => rawurlencode($k).'='.rawurlencode($v), array_keys($query), $query)))
            ->assertRedirect(rtrim((string) config('app.frontend_url'), '/').'/payments/'.$this->payment->reference);

        $this->assertSame(PaymentState::Paid, $this->payment->fresh()?->status);
    }

    public function test_a_redirect_with_a_bad_hmac_changes_nothing_but_still_shows_the_page(): void
    {
        $this->get('/payments/paymob/return?success=true&order=555001&merchant_order_id='.$this->payment->reference.'&hmac=bad')
            ->assertRedirect(rtrim((string) config('app.frontend_url'), '/').'/payments/'.$this->payment->reference);

        $this->assertSame(PaymentState::Pending, $this->payment->fresh()?->status);
    }

    public function test_students_cannot_pay_for_someone_else_by_reusing_a_reference(): void
    {
        $other = User::factory()->student()->create();

        $this->send($this->obj())->assertOk();

        $this->assertFalse($other->isEnrolledIn($this->payment->course));
    }
}
