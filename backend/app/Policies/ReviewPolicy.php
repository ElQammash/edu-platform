<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Enrolled students review a course once they are 30% in; the teacher
 * replies publicly; admins hide reviews that break the rules.
 */
class ReviewPolicy
{
    /**
     * The reviews lists in the panels (scoped to the teacher's courses there).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    /**
     * Write (or rewrite) a review of the course.
     */
    public function review(User $user, Course $course): Response
    {
        if (! $user->isStudent()) {
            return Response::deny(__('reviews.errors.students_only'));
        }

        $enrollment = $user->enrollments()->where('course_id', $course->id)->first();

        if ($enrollment === null) {
            return Response::deny(__('reviews.errors.not_enrolled'));
        }

        return $enrollment->progress_percent >= Review::MIN_PROGRESS
            ? Response::allow()
            : Response::deny(__('reviews.errors.too_early', ['percent' => Review::MIN_PROGRESS]));
    }

    /**
     * Reviews are written by students through the API, never in the panels.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->student_id === $user->id || $user->isAdmin();
    }

    public function reply(User $user, Review $review): bool
    {
        return $user->isAdmin() || $user->teaches($review->course);
    }

    public function hide(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
