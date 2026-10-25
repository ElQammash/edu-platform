<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A lesson's Q&A is shared by everyone in the course: enrolled students ask
 * and reply, the course teacher and admins reply and moderate.
 */
class LessonCommentPolicy
{
    /**
     * Read the Q&A of a lesson and reply in it.
     */
    public function participate(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course();

        return $this->isStaff($user, $course) || ($user->isStudent() && $user->isEnrolledIn($course));
    }

    /**
     * Questions come from enrolled students; staff answer them.
     */
    public function ask(User $user, Lesson $lesson): bool
    {
        return $user->isStudent() && $user->isEnrolledIn($lesson->course());
    }

    public function reply(User $user, LessonComment $question): bool
    {
        return $question->isQuestion() && $this->participate($user, $question->lesson);
    }

    /**
     * Authors fix their own post for a few minutes after posting.
     */
    public function update(User $user, LessonComment $comment): Response
    {
        if ($comment->user_id !== $user->id) {
            return Response::deny();
        }

        return $comment->isEditable()
            ? Response::allow()
            : Response::deny(__('qa.errors.edit_window', ['minutes' => LessonComment::EDIT_MINUTES]));
    }

    public function delete(User $user, LessonComment $comment): bool
    {
        return $comment->user_id === $user->id || $this->moderate($user, $comment);
    }

    /**
     * The course teacher and admins can remove any post.
     */
    public function moderate(User $user, LessonComment $comment): bool
    {
        return $this->isStaff($user, $comment->lesson->course());
    }

    /**
     * Mark a question resolved (or reopen it): staff, or the student who asked.
     */
    public function resolve(User $user, LessonComment $question): bool
    {
        return $question->isQuestion()
            && ($question->user_id === $user->id || $this->moderate($user, $question));
    }

    /**
     * Pick a reply as the answer: whoever may resolve its question.
     */
    public function accept(User $user, LessonComment $reply): bool
    {
        return ! $reply->isQuestion() && $reply->parent !== null && $this->resolve($user, $reply->parent);
    }

    private function isStaff(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->teaches($course);
    }
}
