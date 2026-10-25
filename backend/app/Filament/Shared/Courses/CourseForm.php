<?php

namespace App\Filament\Shared\Courses;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use App\Enums\Language;
use App\Enums\Role;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Course form shared by the admin and teacher panels. Only the admin picks
 * the teacher; in the teacher panel the course always belongs to the
 * signed-in teacher.
 */
class CourseForm
{
    public static function configure(Schema $schema, bool $forAdmin = false): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('courses.course.sections.details'))
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('courses.course.fields.title'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('courses.course.fields.slug'))
                            ->helperText(__('courses.course.hints.slug'))
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            // Left empty, the model generates it from the title.
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        // Markdown, not HTML: rendered safely in the React app.
                        MarkdownEditor::make('description')
                            ->label(__('courses.course.fields.description'))
                            ->disableToolbarButtons(['attachFiles']),
                        FileUpload::make('thumbnail')
                            ->label(__('courses.course.fields.thumbnail'))
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('courses'),
                    ]),

                Section::make(__('courses.course.sections.publishing'))
                    ->columnSpan(1)
                    ->schema([
                        ...($forAdmin ? [
                            Select::make('teacher_id')
                                ->label(__('courses.course.fields.teacher'))
                                ->relationship('teacher', 'name', fn (Builder $query) => $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', Role::Teacher->value)))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ] : []),
                        TextInput::make('price')
                            ->label(__('courses.course.fields.price'))
                            ->helperText(__('courses.course.hints.price'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->suffix(__('courses.currency'))
                            ->default(0)
                            ->required(),
                        Select::make('language')
                            ->label(__('courses.course.fields.language'))
                            ->options(Language::class)
                            ->default(Language::Arabic)
                            ->required(),
                        Select::make('type')
                            ->label(__('courses.course.fields.type'))
                            ->options(CourseType::class)
                            ->default(CourseType::Recorded)
                            ->required(),
                        Select::make('status')
                            ->label(__('courses.course.fields.status'))
                            ->options(CourseStatus::class)
                            ->default(CourseStatus::Draft)
                            ->required(),
                    ]),
            ]);
    }
}
