<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A scheduled live lesson was moved or cancelled. Carries a data snapshot
 * (not the model), so it can still be sent after the lesson is deleted.
 */
class LiveLessonChanged extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const RESCHEDULED = 'live_lesson_rescheduled';

    public const CANCELLED = 'live_lesson_cancelled';

    /**
     * @param  array{lesson_id: int, lesson_title: string, course_id: int, course_slug: string, course_title: string, starts_at: string|null, url: string}  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
        public readonly ?string $previousStartsAt = null,
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
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [...$this->data, 'previous_starts_at' => $this->previousStartsAt];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $lesson = $this->data['lesson_title'];
        $course = $this->data['course_title'];
        $time = fn (?string $iso) => LiveLessonData::formatTime($iso ? CarbonImmutable::parse($iso) : null);

        $mail = (new MailMessage)->greeting(__('live.mail.greeting', ['name' => $notifiable->name]));

        if ($this->type === self::CANCELLED) {
            return $mail
                ->subject(__('live.mail.cancelled_subject', ['lesson' => $lesson]))
                ->line(__('live.mail.cancelled_line', ['lesson' => $lesson, 'course' => $course, 'time' => $time($this->data['starts_at'])]));
        }

        return $mail
            ->subject(__('live.mail.rescheduled_subject', ['lesson' => $lesson]))
            ->line(__('live.mail.rescheduled_line', ['lesson' => $lesson, 'course' => $course]))
            ->line(__('live.mail.old_time', ['time' => $time($this->previousStartsAt)]))
            ->line(__('live.mail.new_time', ['time' => $time($this->data['starts_at'])]))
            ->action(__('live.mail.open_lesson'), $this->data['url']);
    }
}
