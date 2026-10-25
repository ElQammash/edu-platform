<?php

namespace Tests\Feature\Api\V1\Lessons;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonAccessTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $first;

    private Lesson $second;

    private Lesson $third;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        // Sections and lessons are created out of order on purpose.
        $sectionB = Section::factory()->for($this->course)->create(['order' => 2]);
        $sectionA = Section::factory()->for($this->course)->create(['order' => 1]);
        $this->third = Lesson::factory()->for($sectionB)->create(['order' => 1]);
        $this->second = Lesson::factory()->for($sectionA)->create(['order' => 2]);
        $this->first = Lesson::factory()->for($sectionA)->freePreview()->create(['order' => 1, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    }

    private function enrolledStudent(): User
    {
        $student = User::factory()->student()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $this->course->id]);

        return $student;
    }

    public function test_guests_can_watch_free_previews(): void
    {
        $this->getJson(route('api.v1.lessons.show', $this->first))
            ->assertOk()
            ->assertJsonPath('data.video.provider', 'youtube')
            ->assertJsonPath('data.video.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertJsonMissingPath('data.progress');
    }

    public function test_free_preview_of_a_draft_course_is_hidden(): void
    {
        $this->course->update(['status' => 'draft']);

        $this->getJson(route('api.v1.lessons.show', $this->first))->assertForbidden();
    }

    public function test_locked_lessons_are_forbidden_without_enrollment(): void
    {
        $this->getJson(route('api.v1.lessons.show', $this->second))->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->getJson(route('api.v1.lessons.show', $this->second))
            ->assertForbidden();
    }

    public function test_enrolled_student_gets_lesson_with_neighbours_in_curriculum_order(): void
    {
        $this->actingAs($this->enrolledStudent())
            ->getJson(route('api.v1.lessons.show', $this->second))
            ->assertOk()
            ->assertJsonPath('data.previous_lesson_id', $this->first->id)
            ->assertJsonPath('data.next_lesson_id', $this->third->id)
            ->assertJsonPath('data.course.slug', $this->course->slug)
            ->assertJsonPath('data.progress', null);
    }

    public function test_course_teacher_and_admin_can_open_any_lesson(): void
    {
        $this->actingAs($this->course->teacher)->getJson(route('api.v1.lessons.show', $this->third))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->getJson(route('api.v1.lessons.show', $this->third))->assertOk();
        $this->actingAs(User::factory()->teacher()->create())->getJson(route('api.v1.lessons.show', $this->third))->assertForbidden();
    }

    public function test_progress_is_recorded_and_completes_the_lesson_at_ninety_percent(): void
    {
        $student = $this->enrolledStudent();

        $this->actingAs($student)
            ->postJson(route('api.v1.lessons.progress', $this->first), ['watched_seconds' => 300])
            ->assertOk()
            ->assertJsonPath('data.watched_seconds', 300)
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('course_progress_percent', 0);

        $this->actingAs($student)
            ->postJson(route('api.v1.lessons.progress', $this->first), ['watched_seconds' => 540])
            ->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('course_progress_percent', 33);
    }

    public function test_student_can_mark_a_lesson_complete(): void
    {
        $this->actingAs($this->enrolledStudent())
            ->postJson(route('api.v1.lessons.complete', $this->third))
            ->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('course_progress_percent', 33);
    }

    public function test_progress_requires_enrollment_and_valid_data(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->postJson(route('api.v1.lessons.progress', $this->first), ['watched_seconds' => 10])
            ->assertForbidden();

        $this->actingAs($this->enrolledStudent())
            ->postJson(route('api.v1.lessons.progress', $this->first), ['watched_seconds' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['watched_seconds']);
    }

    public function test_guests_cannot_track_progress(): void
    {
        $this->postJson(route('api.v1.lessons.complete', $this->first))->assertUnauthorized();
    }
}
