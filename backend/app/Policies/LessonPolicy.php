<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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

    /**
     * Staff list lessons inside a section they can reach (see SectionPolicy).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->isAdmin() || $user->teaches($lesson->course());
    }

    public function reorder(User $user): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    /**
     * Get the meeting link of a live lesson: enrolled students and staff,
     * only from 15 minutes before the start until the end.
     */
    public function join(User $user, Lesson $lesson): Response
    {
        $course = $lesson->course();

        if (! ($user->isAdmin() || $user->teaches($course) || ($user->isStudent() && $user->isEnrolledIn($course)))) {
            return Response::deny();
        }

        if (! $lesson->isLive() || blank($lesson->meeting_url)) {
            return Response::denyAsNotFound();
        }

        if ($lesson->liveState() === Lesson::LIVE_ENDED) {
            return Response::deny(__('live.errors.ended'));
        }

        return $lesson->canJoinNow()
            ? Response::allow()
            : Response::deny(__('live.errors.not_open_yet', ['minutes' => Lesson::JOIN_OPENS_MINUTES_BEFORE]));
    }

    /**
     * Only enrolled students have progress; previews and staff views are not tracked.
     */
    public function trackProgress(User $user, Lesson $lesson): bool
    {
        return $user->isStudent() && $user->isEnrolledIn($lesson->course());
    }
}
