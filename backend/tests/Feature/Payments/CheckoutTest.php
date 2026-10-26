<?php

namespace Tests\Feature\Payments;

use App\Enums\CourseStatus;
use App\Enums\PaymentState;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.gateway' => 'fake']);
        $this->course = Course::factory()->create(['status' => CourseStatus::Published, 'price' => 750, 'title' => 'Physics Olympiad']);
        $this->student = User::factory()->student()->create();
    }

    public function test_a_student_starts_checkout_at_the_current_price(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount', 750)
            ->assertJsonPath('data.course.slug', $this->course->slug);

        $payment = Payment::query()->sole();
        $this->assertSame(route('payments.fake-checkout', $payment), $response->json('data.checkout_url'));
        $this->assertSame('fake', $payment->gateway);
        $this->assertNotNull($payment->gateway_order_id);

        // A price change later doesn't touch the started payment.
        $this->course->update(['price' => 900]);
        $this->assertSame('750.00', $payment->fresh()?->amount);
    }

    public function test_an_open_checkout_is_reused(): void
    {
        $first = $this->actingAs($this->student)->postJson(route('api.v1.courses.checkout', $this->course))->json('data.reference');

        $this->actingAs($this->student)
            ->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertOk()
            ->assertJsonPath('data.reference', $first);

        // Close to its expiry, a fresh one starts instead.
        $this->travel(55)->minutes();
        $this->actingAs($this->student)
            ->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertCreated()
            ->assertJsonPath('data.reference', fn (string $reference) => $reference !== $first);
    }

    public function test_free_courses_enrolled_students_and_staff_cannot_pay(): void
    {
        $free = Course::factory()->create(['status' => CourseStatus::Published, 'price' => 0]);
        $this->actingAs($this->student)->postJson(route('api.v1.courses.checkout', $free))
            ->assertForbidden()
            ->assertJsonPath('message', __('payments.errors.free'));

        Enrollment::factory()->create(['student_id' => $this->student->id, 'course_id' => $this->course->id]);
        $this->actingAs($this->student)->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertForbidden()
            ->assertJsonPath('message', __('payments.errors.already_enrolled'));

        $this->actingAs(User::factory()->teacher()->create())->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertForbidden();

        $draft = Course::factory()->create(['status' => CourseStatus::Draft, 'price' => 100]);
        $this->actingAs(User::factory()->student()->create())->postJson(route('api.v1.courses.checkout', $draft))
            ->assertNotFound();

        $this->assertSame(0, Payment::count());
    }

    public function test_paymob_checkout_creates_an_intention_and_points_to_unified_checkout(): void
    {
        config([
            'payments.gateway' => 'paymob',
            'payments.paymob.secret_key' => 'sk_test_123',
            'payments.paymob.public_key' => 'pk_test_456',
            'payments.paymob.hmac_secret' => 'hmac',
            'payments.paymob.integration_ids' => [111, 222],
        ]);
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response(['client_secret' => 'cs_test_789', 'intention_order_id' => 555001, 'id' => 'pi_1'], 201),
        ]);

        $url = $this->actingAs($this->student)
            ->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertCreated()
            ->json('data.checkout_url');

        $this->assertSame('https://eg.checkout.paymob.com/?publicKey=pk_test_456&clientSecret=cs_test_789', $url);
        $payment = Payment::query()->sole();
        $this->assertSame('555001', $payment->gateway_order_id);

        Http::assertSent(function (Request $request) use ($payment): bool {
            return $request->hasHeader('Authorization', 'Token sk_test_123')
                && $request['amount'] === 75000
                && $request['currency'] === 'EGP'
                && $request['payment_methods'] === [111, 222]
                && $request['special_reference'] === $payment->reference
                && $request['notification_url'] === route('api.v1.payments.paymob.callback')
                && $request['redirection_url'] === route('payments.paymob.return')
                && $request['billing_data']['email'] === $this->student->email;
        });
    }

    public function test_a_gateway_outage_answers_502_and_marks_the_attempt_failed(): void
    {
        config([
            'payments.gateway' => 'paymob',
            'payments.paymob.secret_key' => 'sk', 'payments.paymob.public_key' => 'pk',
            'payments.paymob.hmac_secret' => 'h', 'payments.paymob.integration_ids' => [1],
        ]);
        Http::fake(['*' => Http::response(['detail' => 'down'], 500)]);

        $this->actingAs($this->student)
            ->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertStatus(502)
            ->assertJsonPath('message', __('payments.errors.gateway_unavailable'));

        $this->assertSame(PaymentState::Failed, Payment::query()->sole()->status);
    }

    public function test_production_refuses_a_fake_checkout_and_stores_nothing(): void
    {
        $this->app['env'] = 'production';

        $this->actingAs($this->student)
            ->postJson(route('api.v1.courses.checkout', $this->course))
            ->assertServerError();

        $this->assertSame(0, Payment::count());
    }

    public function test_students_see_only_their_own_payments(): void
    {
        $mine = Payment::factory()->create(['student_id' => $this->student->id, 'course_id' => $this->course->id]);
        $theirs = Payment::factory()->create();

        $this->actingAs($this->student)->getJson(route('api.v1.payments.show', $mine))
            ->assertOk()
            ->assertJsonPath('data.reference', $mine->reference)
            ->assertJsonMissingPath('data.gateway_order_id');
        $this->actingAs($this->student)->getJson(route('api.v1.payments.show', $theirs))->assertForbidden();
        $this->actingAs($this->student)->getJson(route('api.v1.me.payments'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
