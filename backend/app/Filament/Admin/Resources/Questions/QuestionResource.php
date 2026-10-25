<?php

namespace App\Filament\Admin\Resources\Questions;

use App\Filament\Admin\Resources\Questions\Pages\ListQuestions;
use App\Filament\Shared\Qa\QuestionsTable;
use App\Models\LessonComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every lesson question on the platform, for moderation.
 */
class QuestionResource extends Resource
{
    protected static ?string $model = LessonComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'questions';

    public static function getNavigationLabel(): string
    {
        return __('qa.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('qa.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('qa.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('parent_id');
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table, forAdmin: true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
        ];
    }
}
