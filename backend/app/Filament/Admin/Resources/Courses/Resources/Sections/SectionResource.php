<?php

namespace App\Filament\Admin\Resources\Courses\Resources\Sections;

use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Courses\Resources\Sections\Pages\CreateSection;
use App\Filament\Admin\Resources\Courses\Resources\Sections\Pages\EditSection;
use App\Filament\Shared\Courses\LessonsRelationManager;
use App\Filament\Shared\Courses\SectionForm;
use App\Filament\Shared\Courses\SectionsTable;
use App\Models\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $parentResource = CourseResource::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('courses.section.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('courses.section.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return SectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateSection::route('/create'),
            'edit' => EditSection::route('/{record}/edit'),
        ];
    }
}
