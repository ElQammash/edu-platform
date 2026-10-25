<?php

namespace Tests\Feature\Filament\Teacher;

use App\Filament\Teacher\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Teacher\Resources\Submissions\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionGraded;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class GradingTest extends TestCase
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

    private function assignmentFor(User $teacher, int $maxScore = 20): Assignment
    {
        $lesson = Lesson::factory()->for(Section::factory()->for(Course::factory()->for($teacher, 'teacher')))->create();

        return Assignment::factory()->for($lesson)->create(['max_score' => $maxScore]);
    }

    public function test_pending_list_spans_all_own_courses_and_hides_other_teachers_work(): void
    {
        $mineA = Submission::factory()->create(['assignment_id' => $this->assignmentFor($this->teacher)->id]);
        $mineB = Submission::factory()->create(['assignment_id' => $this->assignmentFor($this->teacher)->id]);
        $graded = Submission::factory()->graded()->create(['assignment_id' => $this->assignmentFor($this->teacher)->id]);
        $theirs = Submission::factory()->create(['assignment_id' => $this->assignmentFor(User::factory()->teacher()->create())->id]);

        Livewire::test(ListSubmissions::class)
            ->assertCanSeeTableRecords([$mineA, $mineB])
            ->assertCanNotSeeTableRecords([$graded, $theirs])
            ->filterTable('status', 'graded')
            ->assertCanSeeTableRecords([$graded])
            ->assertCanNotSeeTableRecords([$mineA, $theirs]);

        $this->assertSame('2', SubmissionResource::getNavigationBadge());
    }

    public function test_teacher_grades_a_submission_and_the_student_is_notified(): void
    {
        Notification::fake();
        $submission = Submission::factory()->create(['assignment_id' => $this->assignmentFor($this->teacher)->id]);

        Livewire::test(ListSubmissions::class)
            ->callAction(TestAction::make('grade')->table($submission), ['score' => 17.5, 'feedback' => 'Nice'])
            ->assertHasNoFormErrors();

        $submission->refresh();
        $this->assertSame('17.50', $submission->score);
        $this->assertSame('Nice', $submission->feedback);
        $this->assertSame($this->teacher->id, $submission->graded_by);
        Notification::assertSentTo($submission->student, SubmissionGraded::class);
    }

    public function test_score_cannot_exceed_the_max(): void
    {
        $submission = Submission::factory()->create(['assignment_id' => $this->assignmentFor($this->teacher, 20)->id]);

        Livewire::test(ListSubmissions::class)
            ->filterTable('status', null)
            ->callAction(TestAction::make('grade')->table($submission), ['score' => 25])
            ->assertHasFormErrors(['score']);

        $this->assertFalse($submission->refresh()->isGraded());
    }

    public function test_teacher_cannot_grade_another_teachers_submission(): void
    {
        $theirs = Submission::factory()->create(['assignment_id' => $this->assignmentFor(User::factory()->teacher()->create())->id]);

        $this->assertFalse($this->teacher->can('grade', $theirs));
    }
}
