<?php

namespace App\Filament\Admin\Resources\Quizzes;

use App\Filament\Admin\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Admin\Resources\Quizzes\Pages\ListQuizzes;
use App\Filament\Shared\Quizzes\AttemptsRelationManager;
use App\Filament\Shared\Quizzes\QuestionsRelationManager;
use App\Filament\Shared\Quizzes\QuizForm;
use App\Filament\Shared\Quizzes\QuizzesTable;
use App\Models\Quiz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Quiz settings, questions and results. Quizzes are created from the
 * lesson form; this is where their questions are written.
 */
class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('quizzes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('quizzes.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return QuizForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizzesTable::configure($table, forAdmin: true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
            AttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}
