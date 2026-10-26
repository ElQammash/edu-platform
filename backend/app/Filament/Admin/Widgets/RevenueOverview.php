<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Notifications\PaymentData;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Online revenue after refunds: this month, last month, and how many
 * enrollments were paid online this month.
 */
class RevenueOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $month = CarbonImmutable::now()->startOfMonth();
        $pending = Payment::query()->where('status', PaymentState::Pending)->count();

        return [
            Stat::make(__('payments.widget.this_month'), $this->money($this->net($month, $month->endOfMonth())))
                ->description(__('payments.widget.net_hint'))
                ->color('success'),
            Stat::make(__('payments.widget.last_month'), $this->money($this->net($month->subMonth(), $month->subMonth()->endOfMonth())))
                ->description(__('payments.widget.net_hint')),
            Stat::make(
                __('payments.widget.paid_enrollments'),
                (string) Enrollment::query()->where('payment_status', PaymentStatus::Paid)->where('enrolled_at', '>=', $month)->count(),
            )->description(__('payments.widget.pending', ['count' => $pending])),
        ];
    }

    /**
     * Money kept from payments made in the period (paid, minus refunds).
     */
    private function net(CarbonImmutable $from, CarbonImmutable $until): float
    {
        $row = Payment::query()
            ->whereIn('status', [PaymentState::Paid, PaymentState::PartiallyRefunded, PaymentState::Refunded])
            ->whereBetween('paid_at', [$from, $until])
            ->selectRaw('coalesce(sum(amount), 0) as paid, coalesce(sum(refunded_amount), 0) as refunded')
            ->first();

        return round((float) ($row->paid ?? 0) - (float) ($row->refunded ?? 0), 2);
    }

    private function money(float $amount): string
    {
        return PaymentData::money($amount);
    }
}
