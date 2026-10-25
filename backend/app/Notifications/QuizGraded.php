<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * In-app notice that the teacher finished grading a quiz attempt's essays.
 * Fully auto-graded attempts show their score at once and need no notice.
 * Stored as data so each student reads it in their own language. Queued
 * (after the transaction commits) and sent by the scheduled queue worker.
 */
class QuizGraded extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'quiz_graded';

    public function __construct(
        public readonly QuizAttempt $attempt,
        public readonly bool $regraded = false,
    ) {}

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
        $quiz = $this->attempt->quiz;
        $course = $quiz->course();

        return [
            'attempt_id' => $this->attempt->id,
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'lesson_id' => $quiz->lesson_id,
            'course_slug' => $course->slug,
            'course_title' => $course->title,
            'score' => (float) $this->attempt->score,
            'max_score' => (float) $this->attempt->max_score,
            'regraded' => $this->regraded,
        ];
    }
}
