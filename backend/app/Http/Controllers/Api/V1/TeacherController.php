<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TeacherResource;
use App\Models\User;

class TeacherController extends Controller
{
    /**
     * GET /teachers/{id}: public profile of an approved teacher with their
     * published courses. Anyone else answers 404, so the endpoint cannot be
     * used to look up students or admins.
     */
    public function show(User $teacher): TeacherResource
    {
        abort_unless($teacher->isTeacher() && $teacher->teacherProfile?->is_approved, 404);

        $teacher->load(['teacherProfile', 'courses' => fn ($q) => $q->published()
            ->withCount('lessons')
            ->with('teacher.teacherProfile')
            ->latest('published_at'),
        ])->loadCount(['courses' => fn ($q) => $q->published()]);

        return new TeacherResource($teacher);
    }
}
