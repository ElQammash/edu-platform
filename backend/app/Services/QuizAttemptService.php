<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Taking a quiz. The server owns the clock: the deadline is set when the
 * attempt starts, answers after it are refused, and an attempt left open
 * past its deadline is submitted automatically. Authorization (QuizPolicy,
 * QuizAttemptPolicy) is the caller's job.
 */
class QuizAttemptService
{
    public function __construct(private readonly QuizGradingService $grading) {}

    /**
     * Resume the student's open attempt, or start a new one with the
     * question and option order fixed (shuffled if the quiz says so).
     */
    public function start(User $student, Quiz $quiz): QuizAttempt
    {
        foreach ($quiz->attempts()->where('student_id', $student->id)->whereNull('submitted_at')->get() as $attempt) {
            $attempt = $this->finalizeIfExpired($attempt->setRelation('quiz', $quiz));
            if ($attempt->isOpen()) {
                return $attempt;
            }
        }

        $quiz->loadMissing('questions.options');

        $questions = $quiz->questions;
        if ($quiz->shuffle) {
            $questions = $questions->shuffle();
        }

        $optionOrder = [];
        foreach ($questions as $question) {
            $ids = $question->options->pluck('id');
            $optionOrder[$question->id] = ($quiz->shuffle ? $ids->shuffle() : $ids)->values()->all();
        }

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'expires_at' => $this->deadline($quiz),
            'max_score' => $quiz->questions->sum('points'),
            'question_order' => $questions->pluck('id')->values()->all(),
            'option_order' => $optionOrder,
        ])->setRelation('quiz', $quiz);
    }

    /**
     * Save answers while the attempt is open (autosave): an option for MCQ
     * and true/false, text for essays. Questions not sent are untouched.
     *
     * @param  list<array{question_id: int, option_id?: int|null, text_answer?: string|null}>  $answers
     *
     * @throws ValidationException when the attempt is closed or an answer does not fit the quiz
     */
    public function saveAnswers(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        $attempt = $this->finalizeIfExpired($attempt);
        if (! $attempt->isOpen()) {
            throw ValidationException::withMessages(['attempt' => __('quizzes.errors.attempt_closed')]);
        }

        $questions = $attempt->quiz->questions()->with('options')->get()->keyBy('id');

        DB::transaction(function () use ($attempt, $answers, $questions) {
            foreach ($answers as $i => $input) {
                /** @var Question|null $question */
                $question = $questions->get($input['question_id']);
                if (! $question) {
                    throw ValidationException::withMessages(["answers.$i.question_id" => __('quizzes.errors.unknown_question')]);
                }

                $values = ['option_id' => null, 'text_answer' => null];

                if ($question->type === QuestionType::Essay) {
                    $values['text_answer'] = filled($input['text_answer'] ?? null) ? (string) $input['text_answer'] : null;
                } elseif (($input['option_id'] ?? null) !== null) {
                    if (! $question->options->contains('id', $input['option_id'])) {
                        throw ValidationException::withMessages(["answers.$i.option_id" => __('quizzes.errors.unknown_option')]);
                    }
                    $values['option_id'] = $input['option_id'];
                }

                AttemptAnswer::updateOrCreate(
                    ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                    $values,
                );
            }
        });

        return $attempt;
    }

    /**
     * Submit and auto-grade. Submitting after the deadline is not possible:
     * expired attempts were already closed at their deadline.
     *
     * @throws ValidationException when the attempt is already closed
     */
    public function submit(QuizAttempt $attempt): QuizAttempt
    {
        $attempt = $this->finalizeIfExpired($attempt);
        if ($attempt->isSubmitted()) {
            throw ValidationException::withMessages(['attempt' => __('quizzes.errors.attempt_closed')]);
        }

        $attempt->forceFill(['submitted_at' => now()])->save();

        return $this->grading->autoGrade($attempt);
    }

    /**
     * An attempt whose time ran out is submitted as it was, dated at its
     * deadline. Called whenever an attempt is read or touched.
     */
    public function finalizeIfExpired(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->isSubmitted() || ! $attempt->isExpired()) {
            return $attempt;
        }

        $attempt->forceFill(['submitted_at' => $attempt->expires_at])->save();

        return $this->grading->autoGrade($attempt);
    }

    /**
     * Start + time limit, but never past the quiz's due date.
     */
    private function deadline(Quiz $quiz): ?\DateTimeInterface
    {
        $limit = $quiz->time_limit_minutes ? now()->addMinutes($quiz->time_limit_minutes) : null;

        if ($quiz->due_at === null) {
            return $limit;
        }

        return $limit === null || $quiz->due_at->lessThan($limit) ? $quiz->due_at : $limit;
    }
}
