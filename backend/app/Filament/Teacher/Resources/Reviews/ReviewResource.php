<?php

namespace App\Filament\Teacher\Resources\Reviews;

use App\Filament\Shared\Reviews\ReviewsTable;
use App\Filament\Teacher\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Reviews of the teacher's own courses, with a public reply to each.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reviews';

    public static function getNavigationLabel(): string
    {
        return __('reviews.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('reviews.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reviews.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('course', fn (Builder $q) => $q->where('teacher_id', Auth::id()));
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
