<?php

namespace App\Filament\Shared\Reviews;

use App\Models\Review;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Course reviews, newest first. Teachers reply to reviews of their own
 * courses; admins also hide (and show again) reviews that break the rules.
 */
class ReviewsTable
{
    public static function configure(Table $table, bool $forAdmin = false): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'course']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('rating')
                    ->label(__('reviews.fields.rating'))
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('reviews.fields.comment'))
                    ->placeholder('—')
                    ->limit(80)
                    ->wrap()
                    ->extraAttributes(['dir' => 'auto'])
                    ->description(fn (Review $record): string => $record->student->name),
                TextColumn::make('course.title')
                    ->label(__('reviews.fields.course'))
                    ->limit(30),
                IconColumn::make('teacher_reply')
                    ->label(__('reviews.actions.reply'))
                    ->state(fn (Review $record): bool => filled($record->teacher_reply))
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->falseIcon(Heroicon::OutlinedMinus),
                TextColumn::make('hidden_at')
                    ->label(__('reviews.fields.status'))
                    ->state(fn (Review $record): string => $record->isHidden() ? 'hidden' : 'visible')
                    ->formatStateUsing(fn (string $state): string => __("reviews.status.{$state}"))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'hidden' ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label(__('reviews.fields.posted'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('reviews.fields.status'))
                    ->options([
                        'unreplied' => __('reviews.status.unreplied'),
                        'visible' => __('reviews.status.visible'),
                        'hidden' => __('reviews.status.hidden'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'unreplied' => $query->whereNull('teacher_reply')->whereNull('hidden_at'),
                        'visible' => $query->whereNull('hidden_at'),
                        'hidden' => $query->whereNotNull('hidden_at'),
                        default => $query,
                    }),
                SelectFilter::make('rating')
                    ->label(__('reviews.fields.rating'))
                    ->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★']),
                SelectFilter::make('course')
                    ->label(__('reviews.fields.course'))
                    ->relationship('course', 'title', fn (Builder $query) => $forAdmin ? $query : $query->where('teacher_id', Auth::id()))
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                self::replyAction(),
                ...($forAdmin ? [self::hideAction()] : []),
            ])
            ->emptyStateHeading(__('reviews.empty'));
    }

    private static function replyAction(): Action
    {
        return Action::make('reply')
            ->label(fn (Review $record): string => filled($record->teacher_reply) ? __('reviews.actions.edit_reply') : __('reviews.actions.reply'))
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->authorize(fn (Review $record): bool => Auth::user()?->can('reply', $record) ?? false)
            ->modalHeading(fn (Review $record): string => $record->student->name.' · '.str_repeat('★', $record->rating))
            ->modalDescription(fn (Review $record): ?string => $record->comment)
            ->fillForm(fn (Review $record): array => ['teacher_reply' => $record->teacher_reply])
            ->schema([
                Textarea::make('teacher_reply')
                    ->label(__('reviews.fields.reply'))
                    ->rows(4)
                    ->maxLength(Review::MAX_LENGTH),
            ])
            ->action(function (Review $record, array $data): void {
                $reply = trim((string) ($data['teacher_reply'] ?? ''));

                $record->update([
                    'teacher_reply' => $reply === '' ? null : $reply,
                    'teacher_replied_at' => $reply === '' ? null : now(),
                ]);
            })
            ->successNotificationTitle(__('reviews.replied'));
    }

    private static function hideAction(): Action
    {
        return Action::make('hide')
            ->label(fn (Review $record): string => $record->isHidden() ? __('reviews.actions.unhide') : __('reviews.actions.hide'))
            ->icon(fn (Review $record): Heroicon => $record->isHidden() ? Heroicon::OutlinedEye : Heroicon::OutlinedEyeSlash)
            ->color(fn (Review $record): string => $record->isHidden() ? 'gray' : 'danger')
            ->authorize(fn (Review $record): bool => Auth::user()?->can('hide', $record) ?? false)
            ->requiresConfirmation()
            ->modalDescription(fn (Review $record): ?string => $record->isHidden() ? null : (string) __('reviews.actions.hide_confirm'))
            ->action(function (Review $record, Action $action): void {
                /** @var User $admin */
                $admin = Auth::user();
                $hide = ! $record->isHidden();

                $record->update([
                    'hidden_at' => $hide ? now() : null,
                    'hidden_by' => $hide ? $admin->id : null,
                ]);

                $action->successNotificationTitle($hide ? __('reviews.hidden') : __('reviews.unhidden'));
            });
    }
}
