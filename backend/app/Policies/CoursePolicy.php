<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Admins manage every course; teachers manage only their own.
 * Everyone (guests included) can see published courses.
 */
class CoursePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $user->teaches($course));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $user->teacherProfile?->is_approved === true);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->teaches($course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /**
     * Whether the user may enroll themselves. Paid courses are enrolled by
     * the admin (manual payment) until online payments exist.
     */
    public function enroll(User $user, Course $course): Response
    {
        if (! $user->isStudent() || ! $course->isPublished()) {
            return Response::deny();
        }

        return $course->isFree()
            ? Response::allow()
            : Response::deny(__('enrollments.paid_course'));
    }

    /**
     * Manual enrollment of a student, used for paid courses.
     */
    public function manageEnrollments(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}
