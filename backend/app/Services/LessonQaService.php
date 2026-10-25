<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use App\Notifications\LessonAnswerAccepted;
use App\Notifications\LessonQuestionAsked;
use App\Notifications\LessonQuestionReplied;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Posting in a lesson's Q&A and the notifications it sends. Who may do
 * what is LessonCommentPolicy's job; callers authorize first.
 */
class LessonQaService
{
    public function ask(User $student, Lesson $lesson, string $body): LessonComment
    {
        return DB::transaction(function () use ($student, $lesson, $body): LessonComment {
            $question = $lesson->comments()->create([
                'user_id' => $student->id,
                'body' => $body,
                'by_staff' => false,
            ]);

            $lesson->course()->teacher->notify(new LessonQuestionAsked($question));

            return $question;
        });
    }

    public function reply(User $user, LessonComment $question, string $body): LessonComment
    {
        return DB::transaction(function () use ($user, $question, $body): LessonComment {
            $course = $question->lesson->course();

            $reply = $question->replies()->create([
                'lesson_id' => $question->lesson_id,
                'user_id' => $user->id,
                'body' => $body,
                'by_staff' => $user->isAdmin() || $user->teaches($course),
            ]);

            $this->threadMembers($question)
                ->reject(fn (User $member): bool => $member->id === $user->id)
                ->each(fn (User $member) => $member->notify(new LessonQuestionReplied($reply)));

            return $reply;
        });
    }

    public function edit(LessonComment $comment, string $body): LessonComment
    {
        $comment->update(['body' => $body, 'edited_at' => now()]);

        return $comment;
    }

    /**
     * Soft delete. A deleted reply stops being the accepted answer.
     */
    public function delete(LessonComment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            LessonComment::query()->where('accepted_reply_id', $comment->id)->update(['accepted_reply_id' => null]);

            $comment->delete();
        });
    }

    public function resolve(LessonComment $question): LessonComment
    {
        $question->update(['resolved_at' => $question->resolved_at ?? now()]);

        return $question;
    }

    /**
     * Reopen a question; it also loses its accepted answer.
     */
    public function reopen(LessonComment $question): LessonComment
    {
        $question->update(['resolved_at' => null, 'accepted_reply_id' => null]);

        return $question;
    }

    /**
     * Pick a reply as the answer, which resolves the question.
     */
    public function accept(User $user, LessonComment $reply): LessonComment
    {
        /** @var LessonComment $question */
        $question = $reply->parent;

        if ($question->accepted_reply_id === $reply->id) {
            return $question;
        }

        return DB::transaction(function () use ($user, $reply, $question): LessonComment {
            $question->update([
                'accepted_reply_id' => $reply->id,
                'resolved_at' => $question->resolved_at ?? now(),
            ]);

            $reply->setRelation('parent', $question);

            collect([$question->user, $reply->user])
                ->unique('id')
                ->reject(fn (User $member): bool => $member->id === $user->id)
                ->each(fn (User $member) => $member->notify(new LessonAnswerAccepted($reply)));

            return $question;
        });
    }

    /**
     * The student who asked and everyone who replied.
     *
     * @return Collection<int, User>
     */
    private function threadMembers(LessonComment $question): Collection
    {
        $ids = $question->replies()->pluck('user_id')->push($question->user_id)->unique();

        return User::query()->whereIn('id', $ids)->get();
    }
}
