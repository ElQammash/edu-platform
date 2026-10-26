<?php

namespace App\Filament\Admin\Resources\Payments\Tables;

use App\Enums\PaymentState;
use App\Models\Payment;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'course']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('payments.fields.student'))
                    ->description(fn (Payment $record): string => $record->student->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('course.title')
                    ->label(__('payments.fields.course'))
                    ->limit(30)
                    ->extraAttributes(['dir' => 'auto']),
                TextColumn::make('amount')
                    ->label(__('payments.fields.amount'))
                    ->money(fn (Payment $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('refunded_amount')
                    ->label(__('payments.fields.refunded_amount'))
                    ->money(fn (Payment $record): string => $record->currency)
                    ->color(fn (Payment $record): string => (float) $record->refunded_amount > 0 ? 'danger' : 'gray')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('payments.fields.status'))
                    ->badge(),
                TextColumn::make('payment_method')
                    ->label(__('payments.fields.method'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('reference')
                    ->label(__('payments.fields.reference'))
                    ->searchable()
                    ->copyable()
                    ->limit(13)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('payments.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('payments.fields.status'))
                    ->options(PaymentState::class)
                    ->multiple(),
                SelectFilter::make('course')
                    ->label(__('payments.fields.course'))
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('payments.fields.from')),
                        DatePicker::make('until')->label(__('payments.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
