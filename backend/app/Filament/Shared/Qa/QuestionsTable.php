<?php

namespace App\Filament\Shared\Qa;

use App\Models\Course;
use App\Models\LessonComment;
use App\Services\LessonQaService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Student questions across the teacher's courses. Opens on "unanswered"
 * (oldest first), so the list is the teacher's Q&A inbox.
 */
class QuestionsTable
{
    public static function configure(Table $table, bool $forAdmin = false): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'lesson.section.course', 'replies.user'])->withCount('replies'))
            ->defaultSort('created_at')
            ->columns(self::columns($forAdmin))
            ->filters([
                SelectFilter::make('status')
                    ->label(__('qa.fields.status'))
                    ->options([
                        'unanswered' => __('qa.status.unanswered'),
                        'answered' => __('qa.status.answered'),
                        'resolved' => __('qa.status.resolved'),
                    ])
                    ->default('unanswered')
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        // @phpstan-ignore method.notFound (local scope)
                        'unanswered' => $query->unanswered(),
                        'answered' => $query->whereNull('resolved_at')->whereHas('replies', fn (Builder $q) => $q->where('by_staff', true)),
                        'resolved' => $query->whereNotNull('resolved_at'),
                        default => $query,
                    }),
                SelectFilter::make('course')
                    ->label(__('qa.fields.course'))
                    ->options(fn (): array => Course::query()
                        ->when(! $forAdmin, fn (Builder $q) => $q->where('teacher_id', Auth::id()))
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('lesson.section', fn (Builder $q) => $q->where('course_id', $data['value']))
                        : $query),
            ])
            ->recordActions(self::actions())
            ->emptyStateHeading(__('qa.empty'));
    }

    /**
     * @return list<TextColumn>
     */
    public static function columns(bool $forAdmin = false): array
    {
        return [
            TextColumn::make('body')
                ->label(__('qa.fields.question'))
                ->limit(70)
                ->wrap()
                ->extraAttributes(['dir' => 'auto'])
                ->description(fn (LessonComment $record): string => $record->user->name)
                ->searchable(),
            TextColumn::make('lesson.title')
                ->label(__('qa.fields.lesson'))
                ->description(fn (LessonComment $record): string => $record->lesson->course()->title)
                ->limit(35),
            TextColumn::make('replies_count')
                ->label(__('qa.fields.replies'))
                ->alignCenter(),
            TextColumn::make('status')
                ->label(__('qa.fields.status'))
                ->state(fn (LessonComment $record): string => self::status($record))
                ->formatStateUsing(fn (string $state): string => __("qa.status.{$state}"))
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'unanswered' => 'warning',
                    'answered' => 'info',
                    default => 'success',
                }),
            TextColumn::make('created_at')
                ->label(__('qa.fields.asked'))
                ->since()
                ->dateTimeTooltip()
                ->sortable(),
            ...($forAdmin ? [
                TextColumn::make('lesson.section.course.teacher.name')
                    ->label(__('courses.course.fields.teacher'))
                    ->toggleable(),
            ] : []),
        ];
    }

    /**
     * @return list<Action>
     */
    public static function actions(): array
    {
        return [
            ThreadAction::make(),
            Action::make('delete')
                ->label(__('qa.actions.delete'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->iconButton()
                ->requiresConfirmation()
                ->authorize(fn (LessonComment $record): bool => Auth::user()?->can('moderate', $record) ?? false)
                ->action(fn (LessonComment $record) => app(LessonQaService::class)->delete($record))
                ->successNotificationTitle(__('qa.deleted')),
        ];
    }

    private static function status(LessonComment $record): string
    {
        if ($record->isResolved()) {
            return 'resolved';
        }

        return $record->replies->contains('by_staff', true) ? 'answered' : 'unanswered';
    }
}
