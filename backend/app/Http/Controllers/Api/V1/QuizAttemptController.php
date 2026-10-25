<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Quizzes\SaveAnswersRequest;
use App\Http\Resources\V1\AttemptResource;
use App\Models\QuizAttempt;
use App\Services\QuizAttemptService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuizAttemptService $attempts) {}

    /**
     * GET /attempts/{id}: the questions to answer, or the result once submitted.
     */
    public function show(QuizAttempt $attempt): AttemptResource
    {
        $attempt->load('quiz.lesson.section.course');

        Gate::authorize('view', $attempt);

        return new AttemptResource($this->attempts->finalizeIfExpired($attempt));
    }

    /**
     * PUT /attempts/{id}/answers: autosave while the attempt is open.
     */
    public function saveAnswers(SaveAnswersRequest $request, QuizAttempt $attempt): Response
    {
        $attempt->load('quiz.lesson.section.course');
        $attempt = $this->attempts->finalizeIfExpired($attempt);

        Gate::authorize('answer', $attempt);

        /** @var list<array{question_id: int, option_id?: int|null, text_answer?: string|null}> $answers */
        $answers = $request->validated('answers');
        $this->attempts->saveAnswers($attempt, $answers);

        return response()->noContent();
    }

    /**
     * POST /attempts/{id}/submit: submit and auto-grade.
     */
    public function submit(QuizAttempt $attempt): AttemptResource
    {
        $attempt->load('quiz.lesson.section.course');
        $attempt = $this->attempts->finalizeIfExpired($attempt);

        Gate::authorize('answer', $attempt);

        return new AttemptResource($this->attempts->submit($attempt));
    }
}
