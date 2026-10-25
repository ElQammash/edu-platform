<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AttemptResource;
use App\Http\Resources\V1\QuizResource;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function __construct(private readonly QuizAttemptService $attempts) {}

    /**
     * GET /quizzes/{id}: rules, the student's attempts and whether they can start.
     */
    public function show(Request $request, Quiz $quiz): QuizResource
    {
        $quiz->load('lesson.section.course');

        Gate::authorize('view', $quiz);

        /** @var User $user */
        $user = $request->user();

        if ($user->isStudent()) {
            $attempts = $quiz->attempts()->where('student_id', $user->id)->latest('started_at')->get()
                ->map(fn ($attempt) => $this->attempts->finalizeIfExpired($attempt->setRelation('quiz', $quiz)));
            $quiz->setRelation('attempts', $attempts);
        }

        return new QuizResource($quiz);
    }

    /**
     * POST /quizzes/{id}/attempts: start a new attempt, or resume the open one.
     */
    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $quiz->load('lesson.section.course');

        Gate::authorize('take', $quiz);

        /** @var User $user */
        $user = $request->user();
        $attempt = $this->attempts->start($user, $quiz);

        return (new AttemptResource($attempt))
            ->response()
            ->setStatusCode($attempt->wasRecentlyCreated ? 201 : 200);
    }
}
