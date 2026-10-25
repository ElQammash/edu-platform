<?php

namespace App\Filament\Shared\Courses;

use App\Models\Section;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The course's sections, shown on the course edit page. Drag rows to
 * reorder; open a section to manage its lessons.
 */
class SectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('lessons'))
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('title')
                    ->label(__('courses.section.fields.title')),
                TextColumn::make('lessons_count')
                    ->label(__('courses.course.fields.lessons_count')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Order value that puts a new section after the existing ones.
     */
    public static function nextOrder(int $courseId): int
    {
        return (int) Section::where('course_id', $courseId)->max('order') + 1;
    }
}
