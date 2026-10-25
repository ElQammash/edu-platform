<?php

namespace Tests\Feature\Filament\Teacher;

use App\Enums\LessonType;
use App\Filament\Shared\Courses\LessonsRelationManager;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages\EditSection;
use App\Filament\Teacher\Widgets\UpcomingLiveSessions;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class LiveLessonManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->actingAs($this->teacher);
        Filament::setCurrentPanel('teacher');
        $this->section = Section::factory()->for(Course::factory()->for($this->teacher, 'teacher'))->create();
    }

    private function lessons(): Testable
    {
        return Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $this->section, 'pageClass' => EditSection::class]);
    }

    public function test_teacher_schedules_a_live_lesson(): void
    {
        $this->lessons()
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Revision session',
                'type' => 'live',
                'live_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duration_seconds' => 90,
                'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            ])
            ->assertHasNoFormErrors();

        $lesson = Lesson::sole();
        $this->assertSame(LessonType::Live, $lesson->type);
        $this->assertSame(5400, $lesson->duration_seconds);
        $this->assertNull($lesson->video_url);
    }

    public function test_live_lessons_need_a_time_and_an_allowed_meeting_link(): void
    {
        $this->lessons()
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'No details',
                'type' => 'live',
                'meeting_url' => 'https://example.com/room',
            ])
            ->assertHasFormErrors(['live_at' => 'required', 'duration_seconds' => 'required', 'meeting_url']);
    }

    public function test_changing_a_live_lesson_to_text_clears_its_live_fields(): void
    {
        $lesson = Lesson::factory()->for($this->section)->live(now()->subDay())->create();

        $this->lessons()
            ->callAction(TestAction::make('edit')->table($lesson), ['type' => 'text', 'content' => 'Notes'])
            ->assertHasNoFormErrors();

        $lesson->refresh();
        $this->assertNull($lesson->live_at);
        $this->assertNull($lesson->meeting_url);
    }

    public function test_attendance_sheet_lists_enrolled_students_and_saves_corrections(): void
    {
        $lesson = Lesson::factory()->for($this->section)->live(now()->subHour())->create();
        [$joined, $absent, $present] = User::factory()->student()->count(3)->create()->all();
        foreach ([$joined, $absent, $present] as $student) {
            Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $this->section->course_id]);
        }
        Attendance::factory()->create(['student_id' => $joined->id, 'lesson_id' => $lesson->id]);

        $this->lessons()
            ->mountAction(TestAction::make('attendance')->table($lesson))
            ->assertSchemaStateSet(['present' => [$joined->id]])
            ->setActionData(['present' => [$present->id]])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing([$present->id], Attendance::pluck('student_id')->all());
        $this->assertSame(Attendance::SOURCE_TEACHER, Attendance::sole()->source);
        $this->assertSame($this->teacher->id, Attendance::sole()->marked_by);
    }

    public function test_the_dashboard_widget_shows_only_the_teachers_upcoming_sessions(): void
    {
        $mine = Lesson::factory()->for($this->section)->live(now()->addHours(2))->create();
        $old = Lesson::factory()->for($this->section)->live(now()->subDays(3))->create();
        $theirs = Lesson::factory()->live(now()->addHours(2))->create();

        Livewire::test(UpcomingLiveSessions::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$old, $theirs]);
    }
}
