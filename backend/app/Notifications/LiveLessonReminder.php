<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your live lesson starts in 24 hours / 1 hour", in-app and by email, in
 * the student's language (User::preferredLocale). Sent from the scheduler.
 */
class LiveLessonReminder extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'live_lesson_reminder';

    public const KIND_DAY = '24h';

    public const KIND_HOUR = '1h';

    /** A lesson deleted before the reminder is sent: drop the job. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly Lesson $lesson,
        public readonly string $kind,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
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
            ...LiveLessonData::of($this->lesson),
            'kind' => $this->kind,
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $data = LiveLessonData::of($this->lesson);
        $when = LiveLessonData::formatTime($this->lesson->live_at);

        return (new MailMessage)
            ->subject(__('live.mail.reminder_subject_'.$this->kind, ['lesson' => $data['lesson_title']]))
            ->greeting(__('live.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('live.mail.reminder_line_'.$this->kind, ['lesson' => $data['lesson_title'], 'course' => $data['course_title']]))
            ->line(__('live.mail.starts_at', ['time' => $when]))
            ->line(__('live.mail.join_hint', ['minutes' => Lesson::JOIN_OPENS_MINUTES_BEFORE]))
            ->action(__('live.mail.open_lesson'), $data['url']);
    }
}
