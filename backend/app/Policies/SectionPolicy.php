<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

/**
 * Sections are managed inside a course; which courses a teacher can reach
 * is limited by the course query and CoursePolicy.
 */
class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Section $section): bool
    {
        return $this->update($user, $section);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Section $section): bool
    {
        return $user->isAdmin() || $user->teaches($section->course);
    }

    public function delete(User $user, Section $section): bool
    {
        return $this->update($user, $section);
    }

    public function reorder(User $user): bool
    {
        return $this->create($user);
    }
}
