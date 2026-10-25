<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A submission (and its file) is private to the student who made it, the
 * teacher of the course and admins.
 */
class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Submission $submission): bool
    {
        return $submission->student_id === $user->id || $this->grade($user, $submission);
    }

    /**
     * Students edit their own submission until it is graded.
     */
    public function update(User $user, Submission $submission): Response
    {
        if ($submission->student_id !== $user->id) {
            return Response::deny();
        }

        return $submission->isGraded()
            ? Response::deny(__('submissions.already_graded'))
            : Response::allow();
    }

    public function grade(User $user, Submission $submission): bool
    {
        return $user->isAdmin() || $user->teaches($submission->assignment->course());
    }

    /**
     * Submissions are created by students through the API, never in the panels.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }
}
