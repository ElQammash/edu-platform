<?php

namespace App\Notifications;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * In-app notice that a teacher graded (or re-graded) a submission.
 *
 * Stored as data, not text, so each student reads it in their own language.
 * Queued (after the transaction commits) and sent by the scheduled queue worker.
 */
class SubmissionGraded extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'submission_graded';

    public function __construct(
        public readonly Submission $submission,
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
        $assignment = $this->submission->assignment;
        $lesson = $assignment->lesson;
        $course = $lesson->course();

        return [
            'submission_id' => $this->submission->id,
            'assignment_id' => $assignment->id,
            'assignment_title' => $assignment->title,
            'lesson_id' => $lesson->id,
            'course_slug' => $course->slug,
            'course_title' => $course->title,
            'score' => (float) $this->submission->score,
            'max_score' => $assignment->max_score,
            'regraded' => $this->regraded,
        ];
    }
}
