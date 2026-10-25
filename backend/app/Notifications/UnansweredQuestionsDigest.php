<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The teacher's daily email listing student questions still waiting for an
 * answer (instead of one email per question). Sent by
 * qa:send-teacher-digest; skipped when there are none.
 */
class UnansweredQuestionsDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** Questions listed in the email; the rest are counted. */
    public const LIMIT = 10;

    /**
     * @param  list<array{student: string, lesson: string, course: string, excerpt: string, asked_at: string}>  $questions  the oldest first, at most LIMIT
     */
    public function __construct(
        public readonly array $questions,
        public readonly int $total,
        public readonly string $panelUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(trans_choice('qa.mail.digest_subject', $this->total, ['count' => $this->total]))
            ->greeting(__('qa.mail.greeting', ['name' => $notifiable->name]))
            ->line(trans_choice('qa.mail.digest_line', $this->total, ['count' => $this->total]));

        foreach ($this->questions as $question) {
            $mail->line('• '.__('qa.mail.digest_item', [
                'student' => $question['student'],
                'lesson' => $question['lesson'],
                'course' => $question['course'],
                'ago' => CarbonImmutable::parse($question['asked_at'])->diffForHumans(),
                'excerpt' => $question['excerpt'],
            ]));
        }

        if ($this->total > count($this->questions)) {
            $mail->line(__('qa.mail.digest_more', ['count' => $this->total - count($this->questions)]));
        }

        return $mail->action(__('qa.mail.digest_action'), $this->panelUrl);
    }
}
