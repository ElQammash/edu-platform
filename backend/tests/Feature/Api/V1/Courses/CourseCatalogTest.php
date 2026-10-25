<?php

namespace Tests\Feature\Api\V1\Courses;

use App\Enums\CourseType;
use App\Enums\Language;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_published_courses(): void
    {
        $published = Course::factory()->create();
        Course::factory()->draft()->create();

        $this->getJson(route('api.v1.courses.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $published->slug)
            ->assertJsonPath('data.0.teacher.name', $published->teacher->name)
            ->assertJsonMissingPath('data.0.teacher.email')
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'total']]);
    }

    public function test_catalog_filters_by_language_type_price_and_search(): void
    {
        $arabicFree = Course::factory()->create(['title' => 'Physics basics', 'language' => Language::Arabic]);
        $englishPaid = Course::factory()->paid()->create(['title' => 'Algebra 1', 'language' => Language::English, 'type' => CourseType::Hybrid]);

        $this->getJson(route('api.v1.courses.index', ['language' => 'en']))
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $englishPaid->id);

        $this->getJson(route('api.v1.courses.index', ['type' => 'hybrid']))
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $englishPaid->id);

        $this->getJson(route('api.v1.courses.index', ['price' => 'free']))
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $arabicFree->id);

        $this->getJson(route('api.v1.courses.index', ['search' => 'physics']))
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $arabicFree->id);
    }

    public function test_catalog_rejects_invalid_filters(): void
    {
        $this->getJson(route('api.v1.courses.index', ['language' => 'fr']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);
    }

    public function test_course_page_shows_curriculum_without_lesson_content(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->for($course)->create();
        Lesson::factory()->for($section)->create(['content' => 'Secret notes', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $response = $this->getJson(route('api.v1.courses.show', $course))
            ->assertOk()
            ->assertJsonPath('data.slug', $course->slug)
            ->assertJsonPath('data.lessons_count', 1)
            ->assertJsonCount(1, 'data.sections.0.lessons')
            ->assertJsonMissingPath('data.sections.0.lessons.0.content')
            ->assertJsonMissingPath('data.sections.0.lessons.0.video_url')
            ->assertJsonMissingPath('data.enrollment');

        $this->assertStringNotContainsString('Secret notes', $response->getContent() ?: '');
        $this->assertStringNotContainsString('dQw4w9WgXcQ', $response->getContent() ?: '');
    }

    public function test_draft_course_page_is_hidden_from_students_but_visible_to_its_teacher(): void
    {
        $course = Course::factory()->draft()->create();

        $this->getJson(route('api.v1.courses.show', $course))->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->getJson(route('api.v1.courses.show', $course))
            ->assertForbidden();

        $this->actingAs($course->teacher)
            ->getJson(route('api.v1.courses.show', $course))
            ->assertOk();
    }

    public function test_course_page_includes_the_students_enrollment_and_completed_lessons(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();
        $section = Section::factory()->for($course)->create();
        [$done, $todo] = Lesson::factory()->count(2)->for($section)->create()->all();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $course->id, 'progress_percent' => 50]);
        LessonProgress::factory()->completed()->create(['student_id' => $student->id, 'lesson_id' => $done->id]);

        $this->actingAs($student)
            ->getJson(route('api.v1.courses.show', $course))
            ->assertOk()
            ->assertJsonPath('data.enrollment.progress_percent', 50)
            ->assertJsonPath('data.sections.0.lessons.0.id', $done->id)
            ->assertJsonPath('data.sections.0.lessons.0.is_completed', true)
            ->assertJsonPath('data.sections.0.lessons.1.id', $todo->id)
            ->assertJsonPath('data.sections.0.lessons.1.is_completed', false);
    }

    public function test_unknown_course_returns_404(): void
    {
        $this->getJson(route('api.v1.courses.show', 'does-not-exist'))->assertNotFound();
    }
}
