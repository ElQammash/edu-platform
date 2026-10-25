<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Review;
use App\Models\TeacherProfile;

/**
 * Keeps the cached ratings in step with the visible reviews: each course's
 * average and count, and each teacher's average over all their courses'
 * reviews (so a course with more reviews weighs more).
 */
class RatingService
{
    public function refresh(Course $course): void
    {
        $reviews = Review::query()->visible()->where('course_id', $course->id);

        $course->forceFill([
            'rating_avg' => round((float) $reviews->avg('rating'), 2),
            'reviews_count' => $reviews->count(),
        ])->saveQuietly();

        $teacherAverage = Review::query()
            ->visible()
            ->whereHas('course', fn ($q) => $q->where('teacher_id', $course->teacher_id))
            ->avg('rating');

        TeacherProfile::query()
            ->where('user_id', $course->teacher_id)
            ->update(['rating' => round((float) $teacherAverage, 2)]);
    }

    /**
     * Count of visible reviews per star, 5 down to 1.
     *
     * @return array<int, int>
     */
    public function breakdown(Course $course): array
    {
        $counts = Review::query()
            ->visible()
            ->where('course_id', $course->id)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $breakdown = [];
        foreach ([5, 4, 3, 2, 1] as $stars) {
            $breakdown[$stars] = (int) ($counts[$stars] ?? 0);
        }

        return $breakdown;
    }
}
