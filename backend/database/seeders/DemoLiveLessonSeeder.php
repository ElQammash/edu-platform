<?php

namespace Database\Seeders;

use App\Enums\LessonType;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonProgressService;
use Illuminate\Database\Seeder;

/**
 * Live sessions at times relative to "now", so every state shows right
 * after seeding: one running, one starting within the hour (its 1-hour
 * reminder goes out on the first scheduler run), one tomorrow (24-hour
 * reminder) and past ones with attendance and a recording. The meeting
 * links are placeholders. Courses that already have a live section are
 * skipped.
 */
class DemoLiveLessonSeeder extends Seeder
{
    private const MEETING = 'https://us02web.zoom.us/j/1234567890';

    private const RECORDING = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    public function run(LessonProgressService $progress): void
    {
        $student1 = User::where('email', 'student1@edu-platform.test')->firstOrFail();
        $student3 = User::where('email', 'student3@edu-platform.test')->firstOrFail();

        $physics = $this->section('physics-secondary-3', 'الحصص اللايف', [
            ['حصة مراجعة الكهربية (لايف)', now()->subDays(2)->setTime(19, 0), 60, self::RECORDING],
            ['حصة حل مسائل (شغالة دلوقتي)', now()->subMinutes(10), 60, null],
            ['حصة أسئلة وأجوبة', now()->addMinutes(55), 45, null],
            ['حصة المراجعة النهائية', now()->addHours(23), 90, null],
        ]);

        if ($physics) {
            // student1 joined the past session; student3 missed it.
            $past = $physics[0];
            Attendance::create([
                'student_id' => $student1->id,
                'lesson_id' => $past->id,
                'attended_at' => $past->live_at?->addMinutes(3),
                'source' => Attendance::SOURCE_JOIN,
            ]);
            $progress->markComplete($student1, $past);
        }

        $this->section('physics-olympiad-preparation', 'Live problem nights', [
            ['Problem night #1', now()->subWeek()->setTime(20, 0), 90, self::RECORDING],
            ['Problem night #2', now()->addDays(6)->setTime(20, 0), 90, null],
        ]);

        // New lessons change every enrolled student's course percentage.
        foreach (Course::whereIn('slug', ['physics-secondary-3', 'physics-olympiad-preparation'])->get() as $course) {
            foreach ($course->students as $student) {
                $progress->refreshCourseProgress($student, $course);
            }
        }

        // student3 attended the first olympiad night.
        $night = Lesson::where('title', 'Problem night #1')->first();
        if ($night && ! $night->attendances()->exists()) {
            Attendance::create([
                'student_id' => $student3->id,
                'lesson_id' => $night->id,
                'attended_at' => $night->live_at,
                'source' => Attendance::SOURCE_TEACHER,
                'marked_by' => $night->course()->teacher_id,
            ]);
        }
    }

    /**
     * @param  list<array{string, \DateTimeInterface, int, string|null}>  $sessions  title, start, minutes, recording
     * @return list<Lesson>|null the created lessons, or null when the section exists
     */
    private function section(string $courseSlug, string $title, array $sessions): ?array
    {
        $course = Course::where('slug', $courseSlug)->firstOrFail();

        if ($course->sections()->where('title', $title)->exists()) {
            return null;
        }

        $section = $course->sections()->create([
            'title' => $title,
            'order' => (int) $course->sections()->max('order') + 1,
        ]);

        $lessons = [];
        foreach ($sessions as $order => [$lessonTitle, $startsAt, $minutes, $recording]) {
            $lessons[] = $section->lessons()->create([
                'title' => $lessonTitle,
                'type' => LessonType::Live,
                'live_at' => $startsAt,
                'duration_seconds' => $minutes * 60,
                'meeting_url' => self::MEETING,
                'recording_url' => $recording,
                'order' => $order + 1,
            ]);
        }

        return $lessons;
    }
}
