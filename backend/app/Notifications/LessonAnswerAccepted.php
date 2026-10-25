<?php

namespace App\Notifications;

use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A reply was picked as the answer to a question. Sent to the student who
 * asked when the teacher picked it (in-app and email), and in-app to the
 * reply's author.
 */
class LessonAnswerAccepted extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'lesson_answer_accepted';

    public bool $deleteWhenMissingModels = true;

    public function __construct(public readonly LessonComment $reply) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $this->isAskerOf($notifiable) ? ['database', 'mail'] : ['database'];
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
            'on_my_question' => $this->isAskerOf($notifiable),
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $data = LessonQaData::of($this->reply);

        return (new MailMessage)
            ->subject(__('qa.mail.accepted_subject', ['lesson' => $data['lesson_title']]))
            ->greeting(__('qa.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('qa.mail.accepted_line', ['lesson' => $data['lesson_title'], 'course' => $data['course_title']]))
            ->line('“'.$data['excerpt'].'”')
            ->action(__('qa.mail.view_answer'), $data['url']);
    }

    private function isAskerOf(User $notifiable): bool
    {
        return $this->reply->parent?->user_id === $notifiable->id;
    }
}
