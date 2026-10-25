<?php

namespace App\Filament\Shared\Courses;

use App\Enums\LessonType;
use App\Filament\Shared\Quizzes\QuizForm;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\Section;
use App\Rules\SupportedVideoUrl;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Lessons of a section, used on the section page of both panels.
 * Drag rows to reorder.
 */
class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('courses.lesson.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('courses.lesson.label');
    }

    public function form(Schema $schema): Schema
    {
        $isVideo = fn (Get $get): bool => self::typeOf($get('type')) === LessonType::Video;

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label(__('courses.lesson.fields.title'))
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label(__('courses.lesson.fields.type'))
                    ->options(LessonType::availableOptions())
                    ->default(LessonType::Video->value)
                    ->required()
                    ->live(),
                TextInput::make('video_url')
                    ->label(__('courses.lesson.fields.video_url'))
                    ->helperText(__('courses.lesson.hints.video_url'))
                    ->url()
                    ->maxLength(255)
                    ->rule(new SupportedVideoUrl)
                    ->required($isVideo)
                    ->visible($isVideo)
                    ->columnSpanFull(),
                // Stored in seconds, edited in minutes.
                TextInput::make('duration_seconds')
                    ->label(__('courses.lesson.fields.duration'))
                    ->helperText(__('courses.lesson.hints.duration'))
                    ->numeric()
                    ->minValue(0.1)
                    ->maxValue(600)
                    ->step(0.5)
                    ->formatStateUsing(fn (?int $state): ?float => $state ? round($state / 60, 1) : null)
                    ->dehydrateStateUsing(fn (mixed $state): ?int => is_numeric($state) ? (int) round((float) $state * 60) : null)
                    ->visible($isVideo),
                Toggle::make('is_free_preview')
                    ->label(__('courses.lesson.fields.is_free_preview'))
                    ->helperText(__('courses.lesson.hints.is_free_preview'))
                    ->inline(false),
                // Markdown, not HTML: rendered safely in the React app.
                MarkdownEditor::make('content')
                    ->label(__('courses.lesson.fields.content'))
                    ->helperText(__('courses.lesson.hints.content'))
                    ->disableToolbarButtons(['attachFiles'])
                    ->required(fn (Get $get): bool => self::typeOf($get('type')) === LessonType::Text)
                    ->columnSpanFull(),

                self::assignmentSection(),
                self::quizSection(),
            ]);
    }

    /**
     * The lesson's optional quiz settings; questions are added on the quiz
     * page. Same rule as the assignment: created only with a title, never
     * removed from this form (that would delete students' attempts).
     */
    private static function quizSection(): FormSection
    {
        $exists = fn (?Lesson $record): bool => $record?->quiz()->exists() ?? false;

        return FormSection::make(__('quizzes.section'))
            ->description(__('quizzes.section_hint'))
            ->collapsible()
            ->collapsed(fn (?Lesson $record): bool => ! $exists($record))
            ->columnSpanFull()
            ->columns(2)
            ->relationship('quiz', condition: fn (Get $get, ?Lesson $record): bool => $exists($record) || filled($get('quiz.title')))
            ->schema(QuizForm::fields(optional: true));
    }

    /**
     * The lesson's optional assignment. A new one is created only when a
     * title is filled in. An existing one is never removed from this form:
     * Filament deletes a related record whose condition turns false, and
     * that would also delete every student's submission.
     */
    private static function assignmentSection(): FormSection
    {
        $exists = fn (?Lesson $record): bool => $record?->assignment()->exists() ?? false;

        return FormSection::make(__('assignments.section'))
            ->description(__('assignments.section_hint'))
            ->collapsible()
            ->collapsed(fn (?Lesson $record): bool => ! $exists($record))
            ->columnSpanFull()
            ->columns(2)
            ->relationship('assignment', condition: fn (Get $get, ?Lesson $record): bool => $exists($record) || filled($get('assignment.title')))
            ->schema([
                TextInput::make('title')
                    ->label(__('assignments.fields.title'))
                    ->maxLength(255)
                    // Once it exists it can be renamed, not blanked out.
                    ->required(fn (?Assignment $record): bool => $record !== null),
                TextInput::make('max_score')
                    ->label(__('assignments.fields.max_score'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->default(100)
                    ->required(fn (Get $get): bool => filled($get('title'))),
                DateTimePicker::make('due_at')
                    ->label(__('assignments.fields.due_at'))
                    ->helperText(__('assignments.hints.due_at'))
                    ->seconds(false),
                MarkdownEditor::make('instructions')
                    ->label(__('assignments.fields.instructions'))
                    ->disableToolbarButtons(['attachFiles'])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('title')
                    ->label(__('courses.lesson.fields.title')),
                TextColumn::make('type')
                    ->label(__('courses.lesson.fields.type'))
                    ->badge(),
                TextColumn::make('duration_seconds')
                    ->label(__('courses.lesson.fields.duration'))
                    ->formatStateUsing(fn (?int $state): string => $state ? (string) round($state / 60, 1) : '—'),
                IconColumn::make('is_free_preview')
                    ->label(__('courses.lesson.fields.is_free_preview'))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Section $section */
                        $section = $this->getOwnerRecord();
                        // New lessons go to the end of the section.
                        $data['order'] = (int) $section->lessons()->max('order') + 1;

                        return self::withoutVideoForTextLessons($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => self::withoutVideoForTextLessons($data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * A text lesson keeps no video link or duration, so a stale link cannot linger.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function withoutVideoForTextLessons(array $data): array
    {
        if (self::typeOf($data['type'] ?? null) !== LessonType::Video) {
            $data['video_url'] = null;
            $data['duration_seconds'] = null;
        }

        return $data;
    }

    private static function typeOf(mixed $value): ?LessonType
    {
        return $value instanceof LessonType ? $value : LessonType::tryFrom(is_string($value) ? $value : '');
    }
}
