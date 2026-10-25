<?php

namespace App\Console\Commands;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\LiveLessonReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Queues the 24-hour and 1-hour reminders for upcoming live lessons.
 * Runs every minute from the scheduler; the live_lesson_reminders table
 * makes it safe to run any number of times.
 */
#[Signature('lessons:send-live-reminders')]
#[Description('Queue 24h and 1h reminders for upcoming live lessons')]
class SendLiveLessonReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        // The 1-hour window first: a lesson created less than an hour ahead
        // only gets the 1-hour reminder, not both at once.
        foreach ([LiveLessonReminder::KIND_HOUR => [0, 60], LiveLessonReminder::KIND_DAY => [60, 24 * 60]] as $kind => [$from, $to]) {
            $lessons = Lesson::query()
                ->with('section.course')
                ->where('type', LessonType::Live)
                ->where('live_at', '>', now()->addMinutes($from))
                ->where('live_at', '<=', now()->addMinutes($to))
                ->whereHas('section.course', fn ($q) => $q->where('status', CourseStatus::Published))
                ->get();

            foreach ($lessons as $lesson) {
                $sent += $this->remind($lesson, $kind);
            }
        }

        $this->info("Queued {$sent} live lesson reminder(s).");

        return self::SUCCESS;
    }

    private function remind(Lesson $lesson, string $kind): int
    {
        $students = User::query()
            ->whereHas('enrollments', fn ($q) => $q->where('course_id', $lesson->course()->id))
            ->get();

        $sent = 0;

        foreach ($students as $student) {
            // The unique index decides: only the first run to insert the row sends.
            $inserted = DB::table('live_lesson_reminders')->insertOrIgnore([
                'lesson_id' => $lesson->id,
                'student_id' => $student->id,
                'kind' => $kind,
                'live_at' => $lesson->live_at,
                'sent_at' => now(),
            ]);

            if ($inserted === 1) {
                $student->notify(new LiveLessonReminder($lesson, $kind));
                $sent++;
            }
        }

        return $sent;
    }
}
