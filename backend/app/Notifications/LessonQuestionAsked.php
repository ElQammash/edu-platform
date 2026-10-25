<?php

namespace App\Notifications;

use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * In-app notice to the course teacher that a student asked a question.
 * Teachers get no email per question: the daily digest
 * (UnansweredQuestionsDigest) lists the ones still waiting.
 */
class LessonQuestionAsked extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'lesson_question';

    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly LessonComment $question) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return self::TYPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return LessonQaData::of($this->question);
    }
}
