<?php

namespace App\Filament\Shared\Quizzes;

use App\Models\Quiz;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizzesTable
{
    public static function configure(Table $table, bool $forAdmin = false): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('lesson.section.course.teacher')
                ->withCount(['questions', 'attempts'])
                ->withSum('questions', 'points'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('quizzes.fields.title'))
                    ->searchable()
                    ->description(fn (Quiz $record): string => $record->lesson->title),
                TextColumn::make('lesson.section.course.title')
                    ->label(__('quizzes.fields.course'))
                    ->limit(30),
                ...($forAdmin ? [
                    TextColumn::make('lesson.section.course.teacher.name')
                        ->label(__('courses.course.fields.teacher')),
                ] : []),
                TextColumn::make('questions_count')
                    ->label(__('quizzes.fields.questions_count')),
                TextColumn::make('questions_sum_points')
                    ->label(__('quizzes.fields.total_points'))
                    ->placeholder('0'),
                TextColumn::make('time_limit_minutes')
                    ->label(__('quizzes.fields.time_limit'))
                    ->suffix(' '.__('quizzes.minutes'))
                    ->placeholder('—'),
                TextColumn::make('due_at')
                    ->label(__('quizzes.fields.due_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('attempts_count')
                    ->label(__('quizzes.fields.attempts_count')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
