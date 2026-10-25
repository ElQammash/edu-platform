<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\QuizGraded;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizGradingService
{
    /**
     * Grade a just-submitted attempt: MCQ and true/false at once, empty
     * answers as 0, written essays left for the teacher. The attempt is
     * final (graded_at, score) when nothing is left to grade.
     */
    public function autoGrade(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->loadMissing('quiz.questions.options');
        $answers = $attempt->answers()->get()->keyBy('question_id');

        DB::transaction(function () use ($attempt, $answers) {
            foreach ($attempt->quiz->questions as $question) {
                /** @var AttemptAnswer $answer */
                $answer = $answers->get($question->id) ?? new AttemptAnswer([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ]);

                if ($question->type->isAutoGraded()) {
                    $correct = $answer->option_id !== null
                        && $question->options->firstWhere('id', $answer->option_id)?->is_correct === true;
                    $answer->points_awarded = $correct ? (string) $question->points : '0';
                    $answer->graded_at = now();
                } elseif (blank($answer->text_answer)) {
                    // Nothing written: nothing for the teacher to grade.
                    $answer->points_awarded = '0';
                    $answer->graded_at = now();
                }

                $answer->save();
            }

            $this->finalizeIfComplete($attempt);
        });

        return $attempt->refresh();
    }

    /**
     * The teacher grades one essay answer; the attempt is finalized (and the
     * student notified) once its last essay has points.
     *
     * @throws ValidationException when the points are outside 0..question points
     */
    public function gradeEssay(AttemptAnswer $answer, User $grader, float $points): AttemptAnswer
    {
        $answer->loadMissing('question', 'attempt');
        $max = $answer->question->points;

        if ($answer->question->type !== QuestionType::Essay) {
            throw ValidationException::withMessages(['points' => __('quizzes.errors.unknown_question')]);
        }

        if ($points < 0 || $points > $max) {
            throw ValidationException::withMessages(['points' => __('quizzes.errors.points_range', ['max' => $max])]);
        }

        DB::transaction(function () use ($answer, $grader, $points) {
            $answer->forceFill([
                'points_awarded' => $points,
                'graded_by' => $grader->id,
                'graded_at' => now(),
            ])->save();

            $attempt = $answer->attempt;
            $wasGraded = $attempt->isGraded();

            $this->finalizeIfComplete($attempt);

            if ($attempt->isGraded()) {
                $attempt->student->notify(new QuizGraded($attempt, regraded: $wasGraded));
            }
        });

        return $answer;
    }

    private function finalizeIfComplete(QuizAttempt $attempt): void
    {
        $answers = $attempt->answers()->get();
        $pending = $answers->contains(fn (AttemptAnswer $a) => ! $a->isGraded());

        if ($pending || ! $attempt->isSubmitted()) {
            return;
        }

        $attempt->forceFill([
            'score' => $answers->sum(fn (AttemptAnswer $a) => (float) $a->points_awarded),
            'graded_at' => $attempt->graded_at ?? now(),
        ])->save();
    }
}
