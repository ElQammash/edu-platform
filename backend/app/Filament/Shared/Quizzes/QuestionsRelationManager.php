<?php

namespace App\Filament\Shared\Quizzes;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The quiz's questions: multiple choice (exactly one correct option),
 * true/false (pick the right answer) or essay. Read-only once students have
 * taken the quiz, so no score changes after the fact.
 */
class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('quizzes.question.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('quizzes.question.label');
    }

    public function isReadOnly(): bool
    {
        /** @var Quiz $quiz */
        $quiz = $this->getOwnerRecord();

        return $quiz->isLocked();
    }

    public function form(Schema $schema): Schema
    {
        $is = fn (QuestionType $type) => fn (Get $get): bool => self::typeOf($get('type')) === $type;

        return $schema
            ->columns(3)
            ->components([
                Select::make('type')
                    ->label(__('quizzes.question.type'))
                    ->options(QuestionType::class)
                    ->default(QuestionType::Mcq->value)
                    ->required()
                    ->live(),
                TextInput::make('points')
                    ->label(__('quizzes.question.points'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(1)
                    ->required(),
                Textarea::make('body')
                    ->label(__('quizzes.question.body'))
                    ->rows(3)
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Repeater::make('options')
                    ->label(__('quizzes.question.options'))
                    ->relationship('options')
                    ->orderColumn('order')
                    ->schema([
                        TextInput::make('body')
                            ->label(__('quizzes.question.option'))
                            ->required()
                            ->maxLength(1000)
                            ->columnSpan(3),
                        Toggle::make('is_correct')
                            ->label(__('quizzes.question.is_correct'))
                            ->inline(false),
                    ])
                    ->columns(4)
                    ->minItems(2)
                    ->maxItems(6)
                    ->defaultItems(4)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $correct = collect(is_array($value) ? $value : [])->filter(fn ($option) => ! empty($option['is_correct']))->count();
                        if ($correct !== 1) {
                            $fail(__('quizzes.question.one_correct'));
                        }
                    })
                    ->visible($is(QuestionType::Mcq))
                    ->columnSpanFull(),
                Radio::make('true_false_answer')
                    ->label(__('quizzes.question.correct_answer'))
                    ->options(['true' => __('quizzes.true'), 'false' => __('quizzes.false')])
                    ->inline()
                    ->required($is(QuestionType::TrueFalse))
                    ->visible($is(QuestionType::TrueFalse))
                    ->afterStateHydrated(function (Radio $component, ?Question $record): void {
                        if ($record?->type === QuestionType::TrueFalse) {
                            $correct = $record->options()->where('is_correct', true)->value('body');
                            $component->state($correct === QuestionOption::FALSE ? 'false' : 'true');
                        }
                    })
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->reorderable('order')
            ->defaultSort('order')
            ->modifyQueryUsing(fn ($query) => $query->withCount('options'))
            ->description(fn () => $this->isReadOnly() ? __('quizzes.locked') : null)
            ->columns([
                TextColumn::make('body')
                    ->label(__('quizzes.question.body'))
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('quizzes.question.type'))
                    ->badge(),
                TextColumn::make('points')
                    ->label(__('quizzes.question.points')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Quiz $quiz */
                        $quiz = $this->getOwnerRecord();
                        $data['order'] = (int) $quiz->questions()->max('order') + 1;

                        return $data;
                    })
                    ->after(fn (Question $record, array $data) => self::syncOptions($record, $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (Question $record, array $data) => self::syncOptions($record, $data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * True/false gets exactly two fixed options; essays have none. (Multiple
     * choice options are saved by the repeater.)
     *
     * @param  array<string, mixed>  $data
     */
    private static function syncOptions(Question $question, array $data): void
    {
        DB::transaction(function () use ($question, $data) {
            if ($question->type === QuestionType::Essay) {
                $question->options()->delete();

                return;
            }

            if ($question->type !== QuestionType::TrueFalse) {
                return;
            }

            $trueIsCorrect = ($data['true_false_answer'] ?? 'true') === 'true';
            $question->options()->whereNotIn('body', [QuestionOption::TRUE, QuestionOption::FALSE])->delete();

            foreach ([QuestionOption::TRUE => $trueIsCorrect, QuestionOption::FALSE => ! $trueIsCorrect] as $body => $correct) {
                $question->options()->updateOrCreate(
                    ['body' => $body],
                    ['is_correct' => $correct, 'order' => $body === QuestionOption::TRUE ? 0 : 1],
                );
            }
        });
    }

    private static function typeOf(mixed $value): ?QuestionType
    {
        return $value instanceof QuestionType ? $value : QuestionType::tryFrom(is_string($value) ? $value : '');
    }
}
