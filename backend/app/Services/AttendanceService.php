<?php

namespace App\Services;

use App\Enums\LessonType;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

/**
 * Attendance of live lessons: recorded automatically when a student joins
 * through the app during the session, and correctable by the teacher.
 * Attendance never affects grades; attending completes the lesson for
 * progress.
 */
class AttendanceService
{
    public function __construct(private readonly LessonProgressService $progress) {}

    /**
     * The student pressed Join during the session. Idempotent.
     */
    public function recordJoin(User $student, Lesson $lesson): Attendance
    {
        $attendance = Attendance::firstOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $lesson->id],
            ['attended_at' => now(), 'source' => Attendance::SOURCE_JOIN],
        );

        $this->progress->markComplete($student, $lesson);

        return $attendance;
    }

    /**
     * The teacher marks a student present or absent on the attendance sheet.
     */
    public function setPresent(Lesson $lesson, User $student, bool $present, User $teacher): void
    {
        if (! $present) {
            Attendance::where('lesson_id', $lesson->id)->where('student_id', $student->id)->delete();

            return;
        }

        Attendance::firstOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $lesson->id],
            ['attended_at' => $lesson->live_at ?? now(), 'source' => Attendance::SOURCE_TEACHER, 'marked_by' => $teacher->id],
        );

        $this->progress->markComplete($student, $lesson);
    }

    /**
     * Sessions held so far in the course and how many the student attended.
     *
     * @return array{attended: int, held: int, percent: float|null}
     */
    public function summary(User $student, Course $course): array
    {
        $held = $course->lessons()
            ->where('lessons.type', LessonType::Live)
            ->whereNotNull('lessons.live_at')
            ->where('lessons.live_at', '<=', now())
            ->pluck('lessons.id');

        $attended = Attendance::query()
            ->where('student_id', $student->id)
            ->whereIn('lesson_id', $held)
            ->count();

        return [
            'attended' => $attended,
            'held' => $held->count(),
            'percent' => $held->isEmpty() ? null : round($attended / $held->count() * 100, 2),
        ];
    }
}
