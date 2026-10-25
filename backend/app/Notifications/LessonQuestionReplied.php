<?php

namespace App\Notifications;

use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A new reply in a thread, for the student who asked and the others who
 * replied. In-app for everyone; the asker also gets an email when the reply
 * is the teacher's (or an admin's).
 */
class LessonQuestionReplied extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'lesson_reply';

    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly LessonComment $reply) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $this->isTeacherAnswerFor($notifiable) ? ['database', 'mail'] : ['database'];
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
        return [
            ...LessonQaData::of($this->reply),
            'by_staff' => $this->reply->by_staff,
            'on_my_question' => $this->reply->parent?->user_id === $notifiable->id,
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $data = LessonQaData::of($this->reply);

        return (new MailMessage)
            ->subject(__('qa.mail.reply_subject', ['lesson' => $data['lesson_title']]))
            ->greeting(__('qa.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('qa.mail.reply_line', ['teacher' => $data['author_name'], 'lesson' => $data['lesson_title'], 'course' => $data['course_title']]))
            ->line('“'.$data['excerpt'].'”')
            ->action(__('qa.mail.view_answer'), $data['url']);
    }

    private function isTeacherAnswerFor(User $notifiable): bool
    {
        return $this->reply->by_staff && $this->reply->parent?->user_id === $notifiable->id;
    }
}
