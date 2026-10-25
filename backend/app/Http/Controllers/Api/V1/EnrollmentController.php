<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EnrollmentResource;
use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{
    /**
     * The signed-in student's courses with their progress.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course' => fn ($q) => $q->with('teacher.teacherProfile')->withCount('lessons')])
            ->latest('enrolled_at')
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Self-enrollment in a free course. Paid courses answer 403 with a
     * message; the student contacts the academy and the admin enrolls them.
     */
    public function store(Request $request, Course $course, EnrollmentService $enrollments): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('enroll', $course);

        $enrollment = $enrollments->enrollSelf($user, $course);

        return (new EnrollmentResource($enrollment))
            ->additional(['message' => __('enrollments.enrolled')])
            ->response()
            ->setStatusCode($enrollment->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
