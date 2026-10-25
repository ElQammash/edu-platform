<?php

namespace App\Filament\Admin\Resources\Submissions;

use App\Filament\Admin\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Shared\Grading\SubmissionsTable;
use App\Models\Submission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Every submission on the platform; admins can grade any of them.
 */
class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'grading';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('quizzes.grading.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('quizzes.grading.assignments_navigation');
    }

    public static function getModelLabel(): string
    {
        return __('grading.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('grading.navigation');
    }

    public static function table(Table $table): Table
    {
        return SubmissionsTable::configure($table, forAdmin: true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
        ];
    }
}
