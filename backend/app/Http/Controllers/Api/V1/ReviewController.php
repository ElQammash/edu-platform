<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Courses\SaveReviewRequest;
use App\Http\Resources\V1\ReviewResource;
use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /**
     * GET /courses/{slug}/reviews: visible reviews, newest first, with the
     * average and a 5-to-1 star breakdown.
     */
    public function index(Course $course, RatingService $ratings): AnonymousResourceCollection
    {
        Gate::authorize('view', $course);

        $reviews = $course->reviews()
            ->visible()
            ->with('student.studentProfile')
            ->latest()
            ->latest('id')
            ->paginate(10);

        return ReviewResource::collection($reviews)->additional(['meta' => ['summary' => [
            'average' => (float) $course->rating_avg,
            'count' => $course->reviews_count,
            'breakdown' => $ratings->breakdown($course),
        ]]]);
    }

    /**
     * GET /courses/{slug}/review: the signed-in student's review (or null),
     * and whether they may write one (with the reason when not).
     */
    public function mine(Request $request, Course $course): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $review = $course->reviews()->where('student_id', $user->id)->with('student.studentProfile')->first();
        $allowed = Gate::inspect('review', [Review::class, $course]);

        return response()->json([
            'data' => $review ? new ReviewResource($review) : null,
            'meta' => [
                'can_review' => $allowed->allowed(),
                'reason' => $allowed->allowed() ? null : $allowed->message(),
                'min_progress' => Review::MIN_PROGRESS,
            ],
        ]);
    }

    /**
     * PUT /courses/{slug}/review: write or rewrite my review.
     */
    public function save(SaveReviewRequest $request, Course $course): JsonResponse
    {
        Gate::authorize('review', [Review::class, $course]);

        /** @var User $user */
        $user = $request->user();
        $comment = trim($request->string('comment')->toString());

        $review = Review::query()->updateOrCreate(
            ['course_id' => $course->id, 'student_id' => $user->id],
            ['rating' => $request->integer('rating'), 'comment' => $comment === '' ? null : $comment],
        );

        return (new ReviewResource($review->load('student.studentProfile')))
            ->response()
            ->setStatusCode($review->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Course $course): Response
    {
        /** @var User $user */
        $user = $request->user();
        $review = $course->reviews()->where('student_id', $user->id)->firstOrFail();

        Gate::authorize('delete', $review);
        $review->delete();

        return response()->noContent();
    }
}
