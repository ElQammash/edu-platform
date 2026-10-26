<?php

namespace App\Filament\Admin\Resources\Payments\Schemas;

use App\Models\Payment;
use App\Models\PaymentEvent;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * A payment, its refunds and every gateway message received for it.
 */
class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')->label(__('payments.fields.status'))->badge(),
                        TextEntry::make('student.name')->label(__('payments.fields.student'))
                            ->helperText(fn (Payment $record): string => $record->student->email),
                        TextEntry::make('course.title')->label(__('payments.fields.course')),
                        TextEntry::make('amount')->label(__('payments.fields.amount'))
                            ->money(fn (Payment $record): string => $record->currency),
                        TextEntry::make('refunded_amount')->label(__('payments.fields.refunded_amount'))
                            ->money(fn (Payment $record): string => $record->currency)
                            ->color(fn (Payment $record): string => (float) $record->refunded_amount > 0 ? 'danger' : 'gray'),
                        TextEntry::make('net')->label(__('payments.fields.net'))
                            ->state(fn (Payment $record): float => $record->netAmount())
                            ->money(fn (Payment $record): string => $record->currency),
                        TextEntry::make('payment_method')->label(__('payments.fields.method'))->placeholder('—'),
                        TextEntry::make('created_at')->label(__('payments.fields.created_at'))->dateTime(),
                        TextEntry::make('paid_at')->label(__('payments.fields.paid_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('refunded_at')->label(__('payments.fields.refunded_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('reference')->label(__('payments.fields.reference'))->copyable(),
                        TextEntry::make('gateway')->label(__('payments.fields.gateway')),
                        TextEntry::make('gateway_order_id')->label(__('payments.fields.gateway_order_id'))->placeholder('—')->copyable(),
                        TextEntry::make('gateway_transaction_id')->label(__('payments.fields.gateway_transaction_id'))->placeholder('—')->copyable(),
                    ]),
                Section::make(__('payments.fields.events'))
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('events')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('type')->label(__('payments.fields.event_type'))
                                    ->formatStateUsing(fn (string $state): string => __("payments.event_types.{$state}"))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        PaymentEvent::TYPE_PAYMENT => 'success',
                                        PaymentEvent::TYPE_REFUND, PaymentEvent::TYPE_VOID => 'info',
                                        PaymentEvent::TYPE_PENDING => 'warning',
                                        default => 'danger',
                                    }),
                                TextEntry::make('amount')->label(__('payments.fields.amount'))->placeholder('—'),
                                TextEntry::make('source')->label(__('payments.fields.event_source')),
                                TextEntry::make('created_at')->label(__('payments.fields.created_at'))->dateTime(),
                                TextEntry::make('payload')->label(__('payments.fields.event_payload'))
                                    ->state(fn (PaymentEvent $record): string => (string) json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                                    ->fontFamily('mono')
                                    ->size('xs')
                                    ->extraAttributes(['class' => 'whitespace-pre-wrap break-all max-h-64 overflow-auto', 'dir' => 'ltr'])
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
