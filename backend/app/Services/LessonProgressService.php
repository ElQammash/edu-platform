<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class LessonProgressService
{
    /** A video counts as watched once this share of it has been reached. */
    public const COMPLETION_THRESHOLD = 0.9;

    /**
     * Record how far a student got in a lesson video, completing the lesson
     * at 90%. Progress never goes backwards (re-watching the start of a video
     * keeps the furthest position).
     *
     * The duration comes from the lesson when the teacher set it, otherwise
     * from the player.
     */
    public function recordWatch(User $student, Lesson $lesson, int $watchedSeconds, ?int $playerDuration = null): LessonProgress
    {
        $progress = $this->progressFor($student, $lesson);
        $duration = $lesson->duration_seconds ?: $playerDuration;

        if ($duration) {
            $watchedSeconds = min($watchedSeconds, $duration);
        }

        $progress->watched_seconds = max($progress->watched_seconds, $watchedSeconds);

        $reachedThreshold = $duration && $progress->watched_seconds >= $duration * self::COMPLETION_THRESHOLD;
        if ($reachedThreshold && ! $progress->isCompleted()) {
            $progress->completed_at = now();
        }

        $completedNow = $progress->isDirty('completed_at');
        $progress->save();

        if ($completedNow) {
            $this->refreshCourseProgress($student, $lesson->course());
        }

        return $progress;
    }

    /**
     * The student's "mark as complete" button, available on every lesson.
     */
    public function markComplete(User $student, Lesson $lesson): LessonProgress
    {
        $progress = $this->progressFor($student, $lesson);

        if (! $progress->isCompleted()) {
            $progress->completed_at = now();
            $progress->save();
            $this->refreshCourseProgress($student, $lesson->course());
        }

        return $progress;
    }

    /**
     * Recalculate enrollments.progress_percent from completed lessons.
     */
    public function refreshCourseProgress(User $student, Course $course): int
    {
        $total = $course->lessons()->count();

        $completed = LessonProgress::query()
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->whereIn('lesson_id', $course->lessons()->select('lessons.id'))
            ->count();

        $percent = $total > 0 ? (int) floor($completed / $total * 100) : 0;

        Enrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->update(['progress_percent' => $percent]);

        return $percent;
    }

    private function progressFor(User $student, Lesson $lesson): LessonProgress
    {
        return LessonProgress::firstOrNew([
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
    }
}
