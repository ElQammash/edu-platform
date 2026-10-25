<?php

namespace Tests\Feature\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Services\LessonProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private LessonProgressService $service;

    private User $student;

    private Course $course;

    /** @var list<Lesson> */
    private array $lessons;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LessonProgressService::class);
        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->create();
        $section = Section::factory()->for($this->course)->create();
        $this->lessons = Lesson::factory()->count(4)->for($section)->create(['duration_seconds' => 100])->all();

        Enrollment::factory()->create(['student_id' => $this->student->id, 'course_id' => $this->course->id]);
    }

    private function enrollmentPercent(): int
    {
        return Enrollment::where('student_id', $this->student->id)->value('progress_percent');
    }

    public function test_lesson_completes_at_ninety_percent(): void
    {
        $progress = $this->service->recordWatch($this->student, $this->lessons[0], 89);
        $this->assertFalse($progress->isCompleted());
        $this->assertSame(0, $this->enrollmentPercent());

        $progress = $this->service->recordWatch($this->student, $this->lessons[0], 90);
        $this->assertTrue($progress->isCompleted());
        $this->assertSame(25, $this->enrollmentPercent());
    }

    public function test_watched_position_never_goes_backwards(): void
    {
        $this->service->recordWatch($this->student, $this->lessons[0], 60);
        $progress = $this->service->recordWatch($this->student, $this->lessons[0], 10);

        $this->assertSame(60, $progress->watched_seconds);
    }

    public function test_watched_seconds_are_capped_at_the_duration(): void
    {
        $progress = $this->service->recordWatch($this->student, $this->lessons[0], 5000);

        $this->assertSame(100, $progress->watched_seconds);
    }

    public function test_player_duration_is_used_when_the_lesson_has_none(): void
    {
        $lesson = $this->lessons[1];
        $lesson->update(['duration_seconds' => null]);

        $this->assertFalse($this->service->recordWatch($this->student, $lesson, 500)->isCompleted());
        $this->assertTrue($this->service->recordWatch($this->student, $lesson, 270, 300)->isCompleted());
    }

    public function test_mark_complete_updates_course_progress(): void
    {
        $this->service->markComplete($this->student, $this->lessons[0]);
        $this->service->markComplete($this->student, $this->lessons[1]);
        $this->service->markComplete($this->student, $this->lessons[1]);

        $this->assertSame(50, $this->enrollmentPercent());
    }

    public function test_progress_in_other_courses_does_not_count(): void
    {
        $otherLesson = Lesson::factory()->create();
        $this->service->markComplete($this->student, $otherLesson);

        $this->assertSame(0, $this->service->refreshCourseProgress($this->student, $this->course));
    }
}
