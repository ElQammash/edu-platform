<?php

namespace App\Filament\Teacher\Resources\Submissions;

use App\Filament\Shared\Grading\SubmissionsTable;
use App\Filament\Teacher\Resources\Submissions\Pages\ListSubmissions;
use App\Models\Submission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * The teacher's grading queue: submissions for assignments in their own
 * courses only. The sidebar badge shows how many wait for a grade.
 */
class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'grading';

    public static function getNavigationLabel(): string
    {
        return __('grading.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('grading.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('grading.navigation');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('assignment.lesson.section.course', fn (Builder $q) => $q->where('teacher_id', Auth::id()));
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()->whereNull('graded_at')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return SubmissionsTable::configure($table);
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
