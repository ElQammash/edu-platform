<?php

namespace App\Filament\Shared\Grading;

use App\Filament\Shared\Quizzes\AttemptsRelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Submitted quiz attempts whose essays wait for the teacher, oldest first.
 */
class QuizAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['student', 'quiz.lesson.section.course'])
                ->whereNotNull('submitted_at')
                ->whereNull('graded_at'))
            ->defaultSort('submitted_at')
            ->columns([
                TextColumn::make('quiz.title')
                    ->label(__('quizzes.label'))
                    ->description(fn ($record): string => $record->quiz->lesson->title)
                    ->searchable(),
                TextColumn::make('quiz.lesson.section.course.title')
                    ->label(__('quizzes.fields.course'))
                    ->limit(30),
                ...array_filter(
                    AttemptsRelationManager::columns(),
                    fn ($column) => in_array($column->getName(), ['student.name', 'submitted_at'], true),
                ),
            ])
            ->recordActions([
                GradeEssaysAction::make(),
            ])
            ->emptyStateHeading(__('quizzes.grading.empty'));
    }
}
