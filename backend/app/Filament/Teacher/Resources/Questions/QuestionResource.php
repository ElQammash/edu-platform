<?php

namespace App\Filament\Teacher\Resources\Questions;

use App\Filament\Shared\Qa\QuestionsTable;
use App\Filament\Teacher\Resources\Questions\Pages\ListQuestions;
use App\Models\LessonComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * The teacher's Q&A inbox: student questions on lessons of their own
 * courses. The sidebar badge counts the unanswered ones.
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
        return parent::getEloquentQuery()
            ->whereNull('parent_id')
            // @phpstan-ignore method.notFound (local scope)
            ->forTeacher((int) Auth::id());
    }

    public static function getNavigationBadge(): ?string
    {
        // @phpstan-ignore method.notFound (local scope)
        $waiting = static::getEloquentQuery()->unanswered()->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
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
