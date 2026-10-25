<?php

namespace Tests\Feature\Services;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Section;
use App\Models\User;
use App\Rules\SupportedMeetingUrl;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_state_and_join_window(): void
    {
        $lesson = Lesson::factory()->live(now()->addMinutes(30), 60)->make();

        $this->assertSame(Lesson::LIVE_UPCOMING, $lesson->liveState());
        $this->assertFalse($lesson->canJoinNow());

        $this->travel(16)->minutes(); // 14 minutes before the start
        $this->assertTrue($lesson->canJoinNow());

        $this->travel(15)->minutes();
        $this->assertSame(Lesson::LIVE_NOW, $lesson->liveState());

        $this->travel(60)->minutes();
        $this->assertSame(Lesson::LIVE_ENDED, $lesson->liveState());
        $this->assertFalse($lesson->canJoinNow());
    }

    public function test_joining_records_attendance_once_and_completes_the_lesson(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->live(now())->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $lesson->course()->id]);

        $service = app(AttendanceService::class);
        $service->recordJoin($student, $lesson);
        $service->recordJoin($student, $lesson);

        $this->assertSame(1, Attendance::count());
        $this->assertSame(Attendance::SOURCE_JOIN, Attendance::sole()->source);
        $this->assertNotNull(LessonProgress::where('lesson_id', $lesson->id)->value('completed_at'));
    }

    public function test_teacher_marks_present_and_absent(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->live(now()->subDay())->create();
        $service = app(AttendanceService::class);

        $service->setPresent($lesson, $student, true, $teacher);
        $this->assertSame($teacher->id, Attendance::sole()->marked_by);

        $service->setPresent($lesson, $student, false, $teacher);
        $this->assertSame(0, Attendance::count());
    }

    public function test_summary_counts_only_sessions_already_held(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->for($course)->create();
        $past = Lesson::factory()->for($section)->live(now()->subDays(2))->create();
        Lesson::factory()->for($section)->live(now()->subDay())->create();
        Lesson::factory()->for($section)->live(now()->addDay())->create();
        Attendance::factory()->create(['student_id' => $student->id, 'lesson_id' => $past->id]);

        $this->assertSame(
            ['attended' => 1, 'held' => 2, 'percent' => 50.0],
            app(AttendanceService::class)->summary($student, $course),
        );
    }

    public function test_meeting_links_must_be_https_on_an_allowed_service(): void
    {
        $this->assertTrue(SupportedMeetingUrl::isSupported('https://us02web.zoom.us/j/123'));
        $this->assertTrue(SupportedMeetingUrl::isSupported('https://meet.google.com/abc-defg-hij'));
        $this->assertTrue(SupportedMeetingUrl::isSupported('https://teams.microsoft.com/l/meetup-join/x'));
        $this->assertFalse(SupportedMeetingUrl::isSupported('http://zoom.us/j/123'));
        $this->assertFalse(SupportedMeetingUrl::isSupported('https://evilzoom.us/j/123'));
        $this->assertFalse(SupportedMeetingUrl::isSupported('https://example.com/meet'));
    }
}
