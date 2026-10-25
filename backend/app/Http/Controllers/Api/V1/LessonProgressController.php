<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lessons\RecordProgressRequest;
use App\Http\Resources\V1\LessonProgressResource;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\LessonProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonProgressController extends Controller
{
    public function __construct(private readonly LessonProgressService $progress) {}

    /**
     * POST /lessons/{lesson}/progress: the player reports the position reached.
     */
    public function store(RecordProgressRequest $request, Lesson $lesson): JsonResponse
    {
        $user = $this->authorizeTracking($request, $lesson);

        $progress = $this->progress->recordWatch(
            $user,
            $lesson,
            $request->integer('watched_seconds'),
            $request->filled('duration_seconds') ? $request->integer('duration_seconds') : null,
        );

        return $this->respond($user, $lesson, $progress);
    }

    /**
     * POST /lessons/{lesson}/complete: the "mark as complete" button.
     */
    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $this->authorizeTracking($request, $lesson);

        return $this->respond($user, $lesson, $this->progress->markComplete($user, $lesson));
    }

    private function authorizeTracking(Request $request, Lesson $lesson): User
    {
        /** @var User $user */
        $user = $request->user();
        $lesson->load('section.course');

        Gate::authorize('trackProgress', $lesson);

        return $user;
    }

    /**
     * Always 200: the first report creates the progress row, but for the
     * client it is the same "progress updated" answer.
     */
    private function respond(User $user, Lesson $lesson, LessonProgress $progress): JsonResponse
    {
        $coursePercent = Enrollment::query()
            ->where('student_id', $user->id)
            ->where('course_id', $lesson->course()->id)
            ->value('progress_percent');

        return (new LessonProgressResource($progress))
            ->additional(['course_progress_percent' => (int) $coursePercent])
            ->response()
            ->setStatusCode(200);
    }
}
