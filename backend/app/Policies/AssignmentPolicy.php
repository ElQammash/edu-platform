<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

/**
 * Assignments are for enrolled students (there is no free preview of an
 * assignment); staff manage them inside courses they can reach.
 */
class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Assignment $assignment): bool
    {
        $course = $assignment->course();

        return $user->isAdmin() || $user->teaches($course) || $user->isEnrolledIn($course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isAdmin() || $user->teaches($assignment->course());
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        return $user->isStudent() && $user->isEnrolledIn($assignment->course());
    }
}
