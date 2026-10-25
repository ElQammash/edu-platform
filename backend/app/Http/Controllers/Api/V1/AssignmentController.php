<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AssignmentResource;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    /**
     * GET /assignments/{id}: instructions plus the student's own submission.
     */
    public function show(Request $request, Assignment $assignment): AssignmentResource
    {
        $assignment->load('lesson.section.course');

        Gate::authorize('view', $assignment);

        /** @var User $user */
        $user = $request->user();

        if ($user->isStudent()) {
            $assignment->load(['submissions' => fn ($q) => $q->where('student_id', $user->id)]);
        }

        return new AssignmentResource($assignment);
    }
}
