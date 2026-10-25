<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\LessonResource;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    /**
     * A lesson for the player: content, embeddable video and the
     * previous/next lesson in the course.
     */
    public function show(Request $request, Lesson $lesson): LessonResource
    {
        $lesson->load('section.course');

        Gate::authorize('view', $lesson);

        /** @var User|null $user */
        $user = $request->user();

        if ($user && Gate::forUser($user)->allows('trackProgress', $lesson)) {
            $lesson->load(['progress' => fn ($q) => $q->where('student_id', $user->id)]);
        }

        // Only those who can open the assignment see it (not free-preview guests).
        // Queried without touching the relation, so it is not serialized otherwise.
        $assignment = $lesson->assignment()->first()?->setRelation('lesson', $lesson);
        if ($user && $assignment && Gate::forUser($user)->allows('view', $assignment)) {
            $lesson->setRelation('assignment', $assignment);
        }

        $ids = $lesson->course()->orderedLessonIds();
        $position = array_search($lesson->id, $ids, true);

        return new LessonResource($lesson, [
            'previous' => $position !== false && $position > 0 ? $ids[$position - 1] : null,
            'next' => $position !== false ? ($ids[$position + 1] ?? null) : null,
        ]);
    }
}
