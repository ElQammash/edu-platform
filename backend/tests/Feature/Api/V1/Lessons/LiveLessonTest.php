<?php

namespace Tests\Feature\Api\V1\Lessons;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveLessonTest extends TestCase
{
    use RefreshDatabase;

    private const MEETING = 'https://us02web.zoom.us/j/1234567890';

    private Course $course;

    private Lesson $lesson;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $this->lesson = Lesson::factory()
            ->for(Section::factory()->for($this->course))
            ->live(now()->addHour(), 60)
            ->create(['meeting_url' => self::MEETING]);
        $this->student = User::factory()->student()->create();
        Enrollment::factory()->create(['student_id' => $this->student->id, 'course_id' => $this->course->id]);
    }

    public function test_the_lesson_shows_the_schedule_but_never_the_meeting_link(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson(route('api.v1.lessons.show', $this->lesson))
            ->assertOk()
            ->assertJsonPath('data.type', 'live')
            ->assertJsonPath('data.live.state', Lesson::LIVE_UPCOMING)
            ->assertJsonPath('data.live.can_join', false);

        $this->assertStringNotContainsString('zoom.us', (string) $response->getContent());

        $this->travel(50)->minutes();
        $response = $this->actingAs($this->student)->getJson(route('api.v1.lessons.show', $this->lesson))
            ->assertJsonPath('data.live.can_join', true);
        $this->assertStringNotContainsString('zoom.us', (string) $response->getContent());
    }

    public function test_joining_before_the_window_is_refused(): void
    {
        $this->actingAs($this->student)
            ->postJson(route('api.v1.lessons.join', $this->lesson))
            ->assertForbidden()
            ->assertJsonPath('message', __('live.errors.not_open_yet', ['minutes' => 15]));

        $this->assertSame(0, Attendance::count());
    }

    public function test_joining_in_the_window_returns_the_link_and_records_attendance_once(): void
    {
        $this->travel(50)->minutes(); // 10 minutes before the start

        $this->actingAs($this->student)
            ->postJson(route('api.v1.lessons.join', $this->lesson))
            ->assertOk()
            ->assertJsonPath('data.meeting_url', self::MEETING);

        $this->travel(20)->minutes();
        $this->actingAs($this->student)->postJson(route('api.v1.lessons.join', $this->lesson))->assertOk();

        $this->assertSame(1, Attendance::count());
        $this->assertSame(Attendance::SOURCE_JOIN, Attendance::sole()->source);
    }

    public function test_joining_after_the_end_is_refused(): void
    {
        $this->travel(3)->hours();

        $this->actingAs($this->student)
            ->postJson(route('api.v1.lessons.join', $this->lesson))
            ->assertForbidden()
            ->assertJsonPath('message', __('live.errors.ended'));
    }

    public function test_students_who_are_not_enrolled_cannot_join(): void
    {
        $this->travel(55)->minutes();

        $this->actingAs(User::factory()->student()->create())
            ->postJson(route('api.v1.lessons.join', $this->lesson))
            ->assertForbidden();
    }

    public function test_the_teacher_can_join_without_an_attendance_row(): void
    {
        $this->travel(55)->minutes();

        $this->actingAs($this->course->teacher)
            ->postJson(route('api.v1.lessons.join', $this->lesson))
            ->assertOk();

        $this->assertSame(0, Attendance::count());
    }

    public function test_after_the_session_the_recording_plays_as_the_video(): void
    {
        $this->lesson->update(['recording_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $this->travel(3)->hours();
        Attendance::factory()->create(['student_id' => $this->student->id, 'lesson_id' => $this->lesson->id]);

        $this->actingAs($this->student)
            ->getJson(route('api.v1.lessons.show', $this->lesson))
            ->assertJsonPath('data.live.state', Lesson::LIVE_ENDED)
            ->assertJsonPath('data.live.has_recording', true)
            ->assertJsonPath('data.video.provider', 'youtube');
    }

    public function test_upcoming_sessions_list_the_students_courses_only(): void
    {
        Lesson::factory()->live(now()->addHours(5))->create(); // another course
        Lesson::factory()->for($this->lesson->section)->live(now()->subDays(3))->create(); // ended

        $this->actingAs($this->student)
            ->getJson(route('api.v1.me.live-sessions'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lesson.id', $this->lesson->id)
            ->assertJsonPath('data.0.state', Lesson::LIVE_UPCOMING);
    }

    public function test_calendar_file_has_the_session_and_no_meeting_link(): void
    {
        $response = $this->get(route('api.v1.lessons.calendar', $this->lesson))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $ics = (string) $response->getContent();
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('DTSTART:'.$this->lesson->live_at?->utc()->format('Ymd\THis\Z'), $ics);
        $this->assertStringNotContainsString('zoom.us', $ics);
    }

    public function test_my_grades_shows_the_attendance_percentage(): void
    {
        $past = Lesson::factory()->for($this->lesson->section)->live(now()->subDays(2))->create();
        Lesson::factory()->for($this->lesson->section)->live(now()->subDay())->create();
        Attendance::factory()->create(['student_id' => $this->student->id, 'lesson_id' => $past->id]);

        $this->actingAs($this->student)
            ->getJson(route('api.v1.me.grades'))
            ->assertJsonPath('data.0.attendance.attended', 1)
            ->assertJsonPath('data.0.attendance.held', 2)
            ->assertJsonPath('data.0.attendance.percent', 50);
    }
}
