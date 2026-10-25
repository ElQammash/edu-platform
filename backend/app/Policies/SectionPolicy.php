<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function update(User $user, Section $section): bool
    {
        return $user->isAdmin() || $user->teaches($section->course);
    }

    public function delete(User $user, Section $section): bool
    {
        return $this->update($user, $section);
    }
}
