<?php

namespace App\Filament\Shared\Quizzes;

use App\Filament\Shared\Grading\GradeEssaysAction;
use App\Models\QuizAttempt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The quiz's results: every attempt with its status, score and pass/fail.
 */
class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('quizzes.attempts.title');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'quiz']))
            ->defaultSort('started_at', 'desc')
            ->columns(self::columns())
            ->recordActions([
                GradeEssaysAction::make(),
            ]);
    }

    /**
     * @return list<Column>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('student.name')
                ->label(__('quizzes.attempts.student'))
                ->searchable(),
            TextColumn::make('status')
                ->label(__('quizzes.attempts.status'))
                ->state(fn (QuizAttempt $record) => match (true) {
                    $record->isGraded() => __('quizzes.attempts.graded'),
                    $record->isSubmitted() => __('quizzes.attempts.pending'),
                    default => __('quizzes.attempts.in_progress'),
                })
                ->badge()
                ->color(fn (QuizAttempt $record): string => match (true) {
                    $record->isGraded() => 'success',
                    $record->isSubmitted() => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('score')
                ->label(__('quizzes.attempts.score'))
                ->state(fn (QuizAttempt $record): ?string => $record->isGraded()
                    ? (float) $record->score.' / '.(float) $record->max_score.' ('.$record->percent().'%)'
                    : null)
                ->placeholder('—'),
            IconColumn::make('passed')
                ->label(__('quizzes.attempts.passed'))
                ->state(fn (QuizAttempt $record): ?bool => $record->passed())
                ->boolean(),
            TextColumn::make('submitted_at')
                ->label(__('quizzes.attempts.submitted_at'))
                ->since()
                ->dateTimeTooltip()
                ->placeholder('—')
                ->sortable(),
        ];
    }
}
