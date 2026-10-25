<?php

namespace App\Filament\Admin\Resources\Courses;

use App\Filament\Admin\Resources\Courses\Pages\CreateCourse;
use App\Filament\Admin\Resources\Courses\Pages\EditCourse;
use App\Filament\Admin\Resources\Courses\Pages\ListCourses;
use App\Filament\Admin\Resources\Courses\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Admin\Resources\Courses\RelationManagers\SectionsRelationManager;
use App\Filament\Shared\Courses\CourseForm;
use App\Filament\Shared\Courses\CoursesTable;
use App\Models\Course;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Every course on the platform. Unlike the teacher panel, the admin picks
 * the teacher and enrolls students manually in paid courses.
 */
class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('courses.course.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('courses.course.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseForm::configure($schema, forAdmin: true);
    }

    public static function table(Table $table): Table
    {
        return CoursesTable::configure($table, forAdmin: true);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
            EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }
}
