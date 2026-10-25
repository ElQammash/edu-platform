<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

/**
 * Who can open a lesson's content (video, text, meeting link).
 * The curriculum (titles, order, duration) is public on the course page.
 */
class LessonPolicy
{
    public function view(?User $user, Lesson $lesson): bool
    {
        $course = $lesson->course();

        if ($lesson->is_free_preview && $course->isPublished()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->isAdmin() || $user->teaches($course)) {
            return true;
        }

        // Enrolled students keep access even if the course is unpublished later.
        return $user->isEnrolledIn($course);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->isAdmin() || $user->teaches($lesson->course());
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    /**
     * Only enrolled students have progress; previews and staff views are not tracked.
     */
    public function trackProgress(User $user, Lesson $lesson): bool
    {
        return $user->isStudent() && $user->isEnrolledIn($lesson->course());
    }
}
