<?php

namespace App\Filament\Admin\Resources\Reviews;

use App\Filament\Admin\Resources\Reviews\Pages\ListReviews;
use App\Filament\Shared\Reviews\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Every course review, for moderation: admins hide reviews that break the
 * rules (they stop counting in the rating) and can reply like a teacher.
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

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table, forAdmin: true);
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
