<?php

namespace App\Http\Resources\V1;

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

/**
 * An attempt with its questions in the order fixed for this student.
 *
 * Correctness is revealed only when QuizAttempt::revealsAnswers() allows it
 * (submitted, fully graded and the quiz's show-answers setting). That covers
 * is_correct, the correct option and per-question points, since points on an
 * auto-graded question give the answer away. Before that the student sees
 * their own answers and, once graded, only the total.
 *
 * @mixin QuizAttempt
 */
class AttemptResource extends AttemptSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quiz = $this->quiz;
        $reveal = $this->revealsAnswers();
        $questions = $quiz->questions()->with('options')->get()->keyBy('id');
        $answers = $this->answers()->get()->keyBy('question_id');

        return [
            ...parent::toArray($request),
            // Lets the client correct for a wrong device clock in its countdown.
            'server_time' => now()->toIso8601String(),
            'answers_revealed' => $reveal,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'pass_score' => $quiz->pass_score,
                'lesson_id' => $quiz->lesson_id,
                'course_slug' => $quiz->course()->slug,
            ],
            'questions' => collect($this->question_order)
                ->map(fn (int $id) => $questions->get($id))
                ->filter()
                ->map(fn (Question $question) => $this->question($question, $answers->get($question->id), $reveal))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function question(Question $question, ?AttemptAnswer $answer, bool $reveal): array
    {
        $order = $this->option_order[$question->id] ?? $question->options->pluck('id')->all();
        $options = collect($order)->map(fn (int $id) => $question->options->firstWhere('id', $id))->filter();

        return [
            'id' => $question->id,
            'type' => $question->type,
            'body' => $question->body,
            'points' => $question->points,
            'options' => $options->map(fn (QuestionOption $option) => [
                'id' => $option->id,
                'body' => $option->body,
                ...($reveal ? ['is_correct' => $option->is_correct] : []),
            ])->values(),
            'answer' => [
                'option_id' => $answer?->option_id,
                'text_answer' => $answer?->text_answer,
            ],
            ...($reveal ? ['points_awarded' => $answer ? (float) $answer->points_awarded : 0.0] : []),
        ];
    }
}
