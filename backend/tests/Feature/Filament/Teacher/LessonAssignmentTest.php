<?php

namespace Tests\Feature\Filament\Teacher;

use App\Filament\Shared\Courses\LessonsRelationManager;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages\EditSection;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class LessonAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);
        Filament::setCurrentPanel('teacher');
        $this->section = Section::factory()->for(Course::factory()->for($teacher, 'teacher'))->create();
    }

    private function manager(): Testable
    {
        return Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $this->section, 'pageClass' => EditSection::class]);
    }

    public function test_a_lesson_gets_an_assignment_when_a_title_is_given(): void
    {
        $this->manager()
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Ohm law',
                'type' => 'text',
                'content' => 'Read chapter 2',
                'assignment' => [
                    'title' => 'Ohm law problems',
                    'max_score' => 20,
                    'instructions' => 'Solve 1 to 5',
                ],
            ])
            ->assertHasNoFormErrors();

        $assignment = Assignment::sole();
        $this->assertSame('Ohm law problems', $assignment->title);
        $this->assertSame(20, $assignment->max_score);
        $this->assertSame(Lesson::sole()->id, $assignment->lesson_id);
    }

    public function test_no_assignment_is_created_without_a_title(): void
    {
        $this->manager()
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Reading',
                'type' => 'text',
                'content' => 'Read chapter 3',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(0, Assignment::count());
    }

    public function test_editing_a_lesson_never_deletes_its_assignment_or_submissions(): void
    {
        $lesson = Lesson::factory()->for($this->section)->text()->create();
        $assignment = Assignment::factory()->for($lesson)->create();
        Submission::factory()->create(['assignment_id' => $assignment->id]);

        $this->manager()
            ->callAction(TestAction::make('edit')->table($lesson), ['title' => 'Renamed lesson'])
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed lesson', $lesson->refresh()->title);
        $this->assertTrue($assignment->exists());
        $this->assertSame(1, Submission::count());

        // Blanking the title is a validation error, not a deletion.
        $this->manager()
            ->callAction(TestAction::make('edit')->table($lesson), ['assignment' => ['title' => '']])
            ->assertHasFormErrors(['assignment.title']);

        $this->assertSame(1, Assignment::count());
    }
}
