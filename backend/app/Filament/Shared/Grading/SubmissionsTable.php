<?php

namespace App\Filament\Shared\Grading;

use App\Models\Course;
use App\Models\Submission;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Submissions to grade. Opens on "pending" (oldest first), so the list is
 * the teacher's grading queue across all their courses.
 */
class SubmissionsTable
{
    public static function configure(Table $table, bool $forAdmin = false): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'assignment.lesson.section.course']))
            ->defaultSort('submitted_at')
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('grading.fields.student'))
                    ->searchable(),
                TextColumn::make('assignment.title')
                    ->label(__('grading.fields.assignment'))
                    ->description(fn (Submission $record): string => $record->assignment->lesson->title)
                    ->searchable()
                    ->limit(40),
                TextColumn::make('assignment.lesson.section.course.title')
                    ->label(__('grading.fields.course'))
                    ->limit(30),
                TextColumn::make('submitted_at')
                    ->label(__('grading.fields.submitted_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('late')
                    ->label('')
                    ->state(fn (Submission $record) => $record->isLate() ? __('grading.late') : null)
                    ->badge()
                    ->color('danger'),
                IconColumn::make('file_path')
                    ->label(__('grading.file'))
                    ->icon(fn (?string $state): ?string => $state ? 'heroicon-o-paper-clip' : null),
                TextColumn::make('score')
                    ->label(__('grading.fields.score'))
                    ->state(fn (Submission $record): ?string => $record->isGraded()
                        ? (float) $record->score.' / '.$record->assignment->max_score
                        : null)
                    ->placeholder(__('grading.pending'))
                    ->badge()
                    ->color(fn (Submission $record): string => $record->isGraded() ? 'success' : 'warning'),
                ...($forAdmin ? [
                    TextColumn::make('assignment.lesson.section.course.teacher.name')
                        ->label(__('courses.course.fields.teacher'))
                        ->toggleable(),
                ] : []),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('grading.fields.status'))
                    ->options([
                        'pending' => __('grading.pending'),
                        'graded' => __('grading.graded'),
                    ])
                    ->default('pending')
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'pending' => $query->whereNull('graded_at'),
                        'graded' => $query->whereNotNull('graded_at'),
                        default => $query,
                    }),
                SelectFilter::make('course')
                    ->label(__('grading.fields.course'))
                    ->options(fn (): array => self::courseOptions($forAdmin))
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('assignment.lesson.section', fn (Builder $q) => $q->where('course_id', $data['value']))
                        : $query),
            ])
            ->recordActions([
                GradeAction::make(),
            ])
            ->emptyStateHeading(__('grading.empty'));
    }

    /**
     * @return array<int, string>
     */
    private static function courseOptions(bool $forAdmin): array
    {
        return Course::query()
            ->when(! $forAdmin, fn (Builder $q) => $q->where('teacher_id', Auth::id()))
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }
}
