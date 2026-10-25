<?php

namespace App\Filament\Teacher\Resources\QuizAttempts;

use App\Filament\Shared\Grading\QuizAttemptsTable;
use App\Filament\Teacher\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Models\QuizAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Quiz attempts in the teacher's own courses whose essays wait for points.
 */
class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'grading/quizzes';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('quizzes.grading.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('quizzes.grading.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('quizzes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('quizzes.grading.navigation');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('quiz.lesson.section.course', fn (Builder $q) => $q->where('teacher_id', Auth::id()));
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()->whereNotNull('submitted_at')->whereNull('graded_at')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return QuizAttemptsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAttempts::route('/'),
        ];
    }
}
