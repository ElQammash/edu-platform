<?php

namespace App\Filament\Shared\Quizzes;

use App\Enums\ShowAnswers;
use App\Models\Quiz;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Quiz settings, used in the lesson form and on the quiz page.
 */
class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components(self::fields());
    }

    /**
     * @param  bool  $optional  in the lesson form: a quiz is created only when a title is given
     * @return list<Component>
     */
    public static function fields(bool $optional = false): array
    {
        return [
            TextInput::make('title')
                ->label(__('quizzes.fields.title'))
                ->maxLength(255)
                ->required(fn (?Quiz $record): bool => ! $optional || $record !== null),
            TextInput::make('time_limit_minutes')
                ->label(__('quizzes.fields.time_limit'))
                ->helperText(__('quizzes.hints.time_limit'))
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(600)
                ->suffix(__('quizzes.minutes')),
            TextInput::make('pass_score')
                ->label(__('quizzes.fields.pass_score'))
                ->numeric()
                ->integer()
                ->minValue(0)
                ->maxValue(100)
                ->default(50)
                ->suffix('%')
                ->required(fn (Get $get): bool => filled($get('title'))),
            TextInput::make('max_attempts')
                ->label(__('quizzes.fields.max_attempts'))
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(20)
                ->default(1)
                ->required(fn (Get $get): bool => filled($get('title'))),
            DateTimePicker::make('due_at')
                ->label(__('quizzes.fields.due_at'))
                ->helperText(__('quizzes.hints.due_at'))
                ->seconds(false),
            Select::make('show_answers')
                ->label(__('quizzes.fields.show_answers'))
                ->helperText(__('quizzes.hints.show_answers'))
                ->options(ShowAnswers::class)
                ->placeholder(__('quizzes.show_answers_default')),
            Toggle::make('shuffle')
                ->label(__('quizzes.fields.shuffle'))
                ->helperText(__('quizzes.hints.shuffle'))
                ->inline(false),
            MarkdownEditor::make('description')
                ->label(__('quizzes.fields.description'))
                ->disableToolbarButtons(['attachFiles'])
                ->columnSpanFull(),
        ];
    }
}
