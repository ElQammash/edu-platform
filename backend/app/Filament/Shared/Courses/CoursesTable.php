<?php

namespace App\Filament\Shared\Courses;

use App\Enums\CourseStatus;
use App\Enums\Language;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoursesTable
{
    public static function configure(Table $table, bool $forAdmin = false): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('teacher')
                ->withCount(['lessons', 'enrollments']))
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label(__('courses.course.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                ...($forAdmin ? [
                    TextColumn::make('teacher.name')
                        ->label(__('courses.course.fields.teacher'))
                        ->searchable()
                        ->sortable(),
                ] : []),
                TextColumn::make('language')
                    ->label(__('courses.course.fields.language'))
                    ->badge(),
                TextColumn::make('type')
                    ->label(__('courses.course.fields.type'))
                    ->badge()
                    ->color('info'),
                TextColumn::make('price')
                    ->label(__('courses.course.fields.price'))
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('courses.course.fields.status'))
                    ->badge(),
                TextColumn::make('lessons_count')
                    ->label(__('courses.course.fields.lessons_count'))
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label(__('courses.course.fields.students_count'))
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('courses.course.fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('courses.course.fields.status'))
                    ->options(CourseStatus::class),
                SelectFilter::make('language')
                    ->label(__('courses.course.fields.language'))
                    ->options(Language::class),
                ...($forAdmin ? [
                    SelectFilter::make('teacher_id')
                        ->label(__('courses.course.fields.teacher'))
                        ->relationship('teacher', 'name')
                        ->searchable()
                        ->preload(),
                ] : []),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
