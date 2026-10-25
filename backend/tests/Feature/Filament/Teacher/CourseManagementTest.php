<?php

namespace Tests\Feature\Filament\Teacher;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Filament\Shared\Courses\LessonsRelationManager;
use App\Filament\Teacher\Resources\Courses\Pages\CreateCourse;
use App\Filament\Teacher\Resources\Courses\Pages\EditCourse;
use App\Filament\Teacher\Resources\Courses\Pages\ListCourses;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages\CreateSection;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages\EditSection;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->actingAs($this->teacher);
        Filament::setCurrentPanel('teacher');
    }

    public function test_teacher_sees_only_their_own_courses(): void
    {
        $mine = Course::factory()->for($this->teacher, 'teacher')->create();
        $theirs = Course::factory()->create();

        Livewire::test(ListCourses::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_teacher_cannot_open_another_teachers_course(): void
    {
        $theirs = Course::factory()->create();

        $this->get(route('filament.teacher.resources.courses.edit', ['record' => $theirs]))->assertNotFound();
    }

    public function test_teacher_creates_a_course_that_belongs_to_them(): void
    {
        $other = User::factory()->teacher()->create();

        Livewire::test(CreateCourse::class)
            ->fillForm([
                'title' => 'الفيزياء للصف الثالث الثانوي',
                'price' => 0,
                'language' => 'ar',
                'type' => 'recorded',
                'status' => 'published',
                // A forged teacher id must be ignored.
                'teacher_id' => $other->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $course = Course::sole();
        $this->assertSame($this->teacher->id, $course->teacher_id);
        $this->assertNotSame('', $course->slug);
        $this->assertSame(CourseStatus::Published, $course->status);
        $this->assertNotNull($course->published_at);
    }

    public function test_teacher_can_edit_their_course(): void
    {
        $course = Course::factory()->for($this->teacher, 'teacher')->draft()->create();

        Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->fillForm(['title' => 'Updated title', 'price' => 250])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated title', $course->refresh()->title);
        $this->assertSame('250.00', $course->price);
    }

    public function test_new_sections_are_added_at_the_end(): void
    {
        $course = Course::factory()->for($this->teacher, 'teacher')->create();
        Section::factory()->for($course)->create(['order' => 3]);

        Livewire::test(CreateSection::class, ['parentRecord' => $course])
            ->fillForm(['title' => 'Chapter 2'])
            ->call('create')
            ->assertHasNoFormErrors();

        $section = Section::where('title', 'Chapter 2')->sole();
        $this->assertSame($course->id, $section->course_id);
        $this->assertSame(4, $section->order);
    }

    public function test_teacher_adds_a_video_lesson_with_duration_in_minutes(): void
    {
        $section = Section::factory()->for(Course::factory()->for($this->teacher, 'teacher'))->create();

        Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Newton laws',
                'type' => LessonType::Video->value,
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
                'duration_seconds' => 12.5,
                'is_free_preview' => true,
            ])
            ->assertHasNoFormErrors();

        $lesson = Lesson::sole();
        $this->assertSame(750, $lesson->duration_seconds);
        $this->assertTrue($lesson->is_free_preview);
        $this->assertSame(1, $lesson->order);
    }

    public function test_lesson_video_must_be_from_a_supported_host(): void
    {
        $section = Section::factory()->for(Course::factory()->for($this->teacher, 'teacher'))->create();

        Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Bad link',
                'type' => LessonType::Video->value,
                'video_url' => 'https://example.com/video.mp4',
            ])
            ->assertHasFormErrors(['video_url']);
    }

    public function test_text_lessons_drop_any_video_link(): void
    {
        $section = Section::factory()->for(Course::factory()->for($this->teacher, 'teacher'))->create();

        Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Reading',
                'type' => LessonType::Text->value,
                'content' => '# Summary',
            ])
            ->assertHasNoFormErrors();

        $lesson = Lesson::sole();
        $this->assertSame(LessonType::Text, $lesson->type);
        $this->assertNull($lesson->video_url);
    }
}
