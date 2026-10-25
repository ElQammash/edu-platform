<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        $course = $quiz->course();

        return $user->isAdmin() || $user->teaches($course) || $user->isEnrolledIn($course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin() || $user->teaches($quiz->course());
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    /**
     * Start (or resume) an attempt: enrolled students, before the due date,
     * within the attempt limit. An open attempt can always be resumed.
     */
    public function take(User $user, Quiz $quiz): Response
    {
        if (! $user->isStudent() || ! $user->isEnrolledIn($quiz->course())) {
            return Response::deny();
        }

        $attempts = $quiz->attempts()->where('student_id', $user->id)->get();

        if ($attempts->contains(fn ($attempt) => $attempt->isOpen())) {
            return Response::allow();
        }

        if ($quiz->isPastDue()) {
            return Response::deny(__('quizzes.errors.closed'));
        }

        if (! $quiz->questions()->exists()) {
            return Response::deny(__('quizzes.errors.no_questions'));
        }

        return $attempts->count() < $quiz->max_attempts
            ? Response::allow()
            : Response::deny(__('quizzes.errors.no_attempts_left'));
    }
}
