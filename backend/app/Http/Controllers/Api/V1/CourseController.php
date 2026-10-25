<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Courses\CourseIndexRequest;
use App\Http\Resources\V1\CourseResource;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * The public catalog: published courses only, newest first, or highest
     * rated first (sort=rating; more reviews win a tie).
     */
    public function index(CourseIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $courses = Course::query()
            ->published()
            ->with('teacher.teacherProfile')
            ->withCount('lessons')
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($filters['language'] ?? null, fn (Builder $q, string $language) => $q->where('language', $language))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['teacher'] ?? null, fn (Builder $q, int|string $teacher) => $q->where('teacher_id', $teacher))
            ->when(($filters['price'] ?? null) === 'free', fn (Builder $q) => $q->where('price', 0))
            ->when(($filters['price'] ?? null) === 'paid', fn (Builder $q) => $q->where('price', '>', 0))
            ->when(($filters['sort'] ?? null) === 'rating', fn (Builder $q) => $q->orderByDesc('rating_avg')->orderByDesc('reviews_count'))
            ->latest('published_at')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();

        return CourseResource::collection($courses);
    }

    /**
     * Course page with its curriculum. Lesson content is not included; the
     * signed-in student's enrollment and completed lessons are.
     */
    public function show(Request $request, Course $course): CourseResource
    {
        Gate::authorize('view', $course);

        $userId = $request->user()?->id;

        $course->load([
            'teacher.teacherProfile',
            'sections.lessons' => fn ($q) => $userId
                ? $q->with(['progress' => fn ($p) => $p->where('student_id', $userId)])
                : $q,
        ])
            ->loadCount(['lessons', 'enrollments'])
            ->loadSum('lessons', 'duration_seconds');

        if ($userId) {
            $course->load(['enrollments' => fn ($q) => $q->where('student_id', $userId)]);
        }

        return new CourseResource($course);
    }
}
