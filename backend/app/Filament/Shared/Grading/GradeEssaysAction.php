<?php

namespace App\Filament\Shared\Grading;

use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizGradingService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Give points to an attempt's essay answers (or change them). Grading the
 * last one finalizes the attempt and notifies the student.
 */
class GradeEssaysAction
{
    public static function make(): Action
    {
        return Action::make('gradeEssays')
            ->label(__('quizzes.grading.grade'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->visible(fn (QuizAttempt $record): bool => $record->isSubmitted() && self::essays($record) !== [])
            ->authorize(fn (QuizAttempt $record): bool => Auth::user()?->can('grade', $record) ?? false)
            ->modalHeading(fn (QuizAttempt $record): string => __('quizzes.grading.modal_heading', [
                'student' => $record->student->name,
                'quiz' => $record->quiz->title,
            ]))
            ->modalWidth('3xl')
            ->fillForm(fn (QuizAttempt $record): array => collect(self::essays($record))
                ->mapWithKeys(fn (AttemptAnswer $answer) => ['points_'.$answer->id => $answer->points_awarded])
                ->all())
            ->schema(fn (QuizAttempt $record): array => array_map(
                fn (AttemptAnswer $answer) => Section::make($answer->question->body)
                    ->compact()
                    ->schema([
                        TextEntry::make('answer_'.$answer->id)
                            ->hiddenLabel()
                            ->state($answer->text_answer ?? __('quizzes.grading.no_answer'))
                            ->extraAttributes(['dir' => 'auto', 'class' => 'whitespace-pre-line']),
                        TextInput::make('points_'.$answer->id)
                            ->label(__('quizzes.grading.points', ['max' => $answer->question->points]))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue($answer->question->points)
                            ->step(0.25)
                            ->required(),
                    ]),
                self::essays($record),
            ))
            ->modalSubmitActionLabel(__('quizzes.grading.save'))
            ->action(function (QuizAttempt $record, array $data): void {
                /** @var User $grader */
                $grader = Auth::user();
                $grading = app(QuizGradingService::class);

                DB::transaction(function () use ($record, $data, $grader, $grading) {
                    foreach (self::essays($record) as $answer) {
                        $grading->gradeEssay($answer, $grader, (float) $data['points_'.$answer->id]);
                    }
                });
            })
            ->successNotificationTitle(__('quizzes.grading.saved'));
    }

    /**
     * Written essay answers of the attempt, in the attempt's question order.
     *
     * @return list<AttemptAnswer>
     */
    private static function essays(QuizAttempt $attempt): array
    {
        $answers = $attempt->answers()
            ->with('question')
            ->whereHas('question', fn ($q) => $q->where('type', QuestionType::Essay))
            ->whereNotNull('text_answer')
            ->get()
            ->sortBy(fn (AttemptAnswer $answer) => array_search($answer->question_id, $attempt->question_order, true));

        return array_values($answers->all());
    }
}
