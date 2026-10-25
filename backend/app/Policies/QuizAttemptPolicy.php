<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * An attempt belongs to its student; the course teacher and admins can
 * see and grade it.
 */
class QuizAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->student_id === $user->id || $this->grade($user, $attempt);
    }

    /**
     * Save answers or submit: only the student, only while it is open.
     */
    public function answer(User $user, QuizAttempt $attempt): Response
    {
        if ($attempt->student_id !== $user->id) {
            return Response::deny();
        }

        return $attempt->isOpen()
            ? Response::allow()
            : Response::deny(__('quizzes.errors.attempt_closed'));
    }

    public function grade(User $user, QuizAttempt $attempt): bool
    {
        return $user->isAdmin() || $user->teaches($attempt->quiz->course());
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, QuizAttempt $attempt): bool
    {
        return $user->isAdmin();
    }
}
