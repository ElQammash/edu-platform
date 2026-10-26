<?php

namespace Tests\Feature\Filament\Admin;

use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Payments\Pages\ListPayments;
use App\Filament\Admin\Resources\Payments\Pages\ViewPayment;
use App\Filament\Admin\Widgets\RevenueOverview;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    public function test_admins_list_and_filter_payments_including_partial_and_full_refunds(): void
    {
        $paid = Payment::factory()->paid()->create();
        $partial = Payment::factory()->paid()->create(['status' => PaymentState::PartiallyRefunded, 'refunded_amount' => 100]);
        $refunded = Payment::factory()->paid()->create(['status' => PaymentState::Refunded, 'refunded_amount' => 500]);
        $pending = Payment::factory()->create();

        Livewire::test(ListPayments::class)
            ->assertCanSeeTableRecords([$paid, $partial, $refunded, $pending])
            ->assertTableColumnStateSet('refunded_amount', '100.00', $partial)
            ->filterTable('status', [PaymentState::PartiallyRefunded->value, PaymentState::Refunded->value])
            ->assertCanSeeTableRecords([$partial, $refunded])
            ->assertCanNotSeeTableRecords([$paid, $pending]);
    }

    public function test_the_payment_page_shows_refunds_and_gateway_messages(): void
    {
        $payment = Payment::factory()->paid()->create(['status' => PaymentState::PartiallyRefunded, 'refunded_amount' => 150]);
        $payment->events()->create([
            'gateway_transaction_id' => '9100', 'type' => PaymentEvent::TYPE_REFUND, 'amount' => 150,
            'source' => PaymentEvent::SOURCE_CALLBACK, 'payload' => ['id' => 9100, 'is_refund' => true], 'created_at' => now(),
        ]);

        Livewire::test(ViewPayment::class, ['record' => $payment->reference])
            ->assertOk()
            ->assertSee(__('enums.payment_state.partially_refunded'))
            ->assertSee(__('payments.event_types.refund'))
            ->assertSee('is_refund');
    }

    public function test_payments_cannot_be_created_or_edited_in_the_panel(): void
    {
        $this->assertFalse(User::query()->firstOrFail()->can('create', Payment::class));
        $this->assertFalse(User::query()->firstOrFail()->can('update', Payment::factory()->create()));
    }

    public function test_the_revenue_widget_counts_money_kept_after_refunds(): void
    {
        Payment::factory()->paid()->create(['amount' => 750]);
        Payment::factory()->paid()->create(['amount' => 500, 'status' => PaymentState::PartiallyRefunded, 'refunded_amount' => 200]);
        Payment::factory()->paid()->create(['amount' => 300, 'status' => PaymentState::Refunded, 'refunded_amount' => 300]);
        Payment::factory()->create(['amount' => 999]);
        Enrollment::factory()->create(['payment_status' => PaymentStatus::Paid, 'enrolled_at' => now()]);

        Livewire::test(RevenueOverview::class)
            ->assertSee('1,050.00');
    }

    public function test_teachers_cannot_open_payments(): void
    {
        $this->actingAs(User::factory()->teacher()->create());

        $this->get('/admin/payments')->assertForbidden();
    }
}
