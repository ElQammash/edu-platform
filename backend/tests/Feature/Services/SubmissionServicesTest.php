<?php

namespace Tests\Feature\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionGraded;
use App\Services\GradingService;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubmissionServicesTest extends TestCase
{
    use RefreshDatabase;

    private SubmissionService $submissions;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Submission::DISK);
        $this->submissions = app(SubmissionService::class);
        $this->student = User::factory()->student()->create();
    }

    public function test_submitting_twice_updates_the_same_submission_and_replaces_the_file(): void
    {
        $assignment = Assignment::factory()->create();

        $first = $this->submissions->submit($this->student, $assignment, 'v1', UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'));
        $oldPath = $first->file_path;
        Storage::disk(Submission::DISK)->assertExists((string) $oldPath);

        $second = $this->submissions->submit($this->student, $assignment, 'v2', UploadedFile::fake()->image('b.png'));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Submission::count());
        $this->assertSame('v2', $second->content);
        $this->assertSame('b.png', $second->file_name);
        Storage::disk(Submission::DISK)->assertMissing((string) $oldPath);
        Storage::disk(Submission::DISK)->assertExists((string) $second->file_path);
    }

    public function test_a_submission_needs_text_or_a_file(): void
    {
        $this->expectException(ValidationException::class);

        $this->submissions->submit($this->student, Assignment::factory()->create(), '   ');
    }

    public function test_removing_the_only_file_without_text_is_rejected(): void
    {
        $assignment = Assignment::factory()->create();
        $this->submissions->submit($this->student, $assignment, null, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));

        $this->expectException(ValidationException::class);

        $this->submissions->submit($this->student, $assignment, null, removeFile: true);
    }

    public function test_late_is_derived_from_the_deadline(): void
    {
        $assignment = Assignment::factory()->pastDue()->create();
        $submission = $this->submissions->submit($this->student, $assignment, 'late answer');

        $this->assertTrue($submission->isLate());

        // Extending the deadline clears the flag.
        $assignment->update(['due_at' => now()->addDay()]);
        $this->assertFalse($submission->refresh()->isLate());
    }

    public function test_graded_submissions_cannot_be_changed(): void
    {
        $assignment = Assignment::factory()->create();
        Submission::factory()->graded()->create(['assignment_id' => $assignment->id, 'student_id' => $this->student->id]);

        $this->expectException(ValidationException::class);

        $this->submissions->submit($this->student, $assignment, 'edit after grading');
    }

    public function test_grading_records_the_grader_and_notifies_the_student(): void
    {
        Notification::fake();
        $teacher = User::factory()->teacher()->create();
        $submission = Submission::factory()->create(['student_id' => $this->student->id]);

        app(GradingService::class)->grade($submission, $teacher, 85.5, 'Good work');

        $submission->refresh();
        $this->assertSame('85.50', $submission->score);
        $this->assertSame($teacher->id, $submission->graded_by);
        $this->assertTrue($submission->isGraded());
        Notification::assertSentTo($this->student, SubmissionGraded::class, fn (SubmissionGraded $n) => ! $n->regraded);
    }

    public function test_regrading_sends_a_regraded_notification(): void
    {
        Notification::fake();
        $teacher = User::factory()->teacher()->create();
        $submission = Submission::factory()->graded(50)->create(['student_id' => $this->student->id]);

        app(GradingService::class)->grade($submission, $teacher, 70);

        Notification::assertSentTo($this->student, SubmissionGraded::class, fn (SubmissionGraded $n) => $n->regraded);
    }

    public function test_score_must_be_within_the_assignment_range(): void
    {
        $submission = Submission::factory()->create();

        $this->expectException(ValidationException::class);

        app(GradingService::class)->grade($submission, User::factory()->teacher()->create(), 101);
    }

    public function test_notification_is_stored_as_data(): void
    {
        $submission = Submission::factory()->create(['student_id' => $this->student->id]);

        app(GradingService::class)->grade($submission, User::factory()->teacher()->create(), 90);

        $notification = $this->student->notifications()->sole();
        $this->assertSame(SubmissionGraded::TYPE, $notification->type);
        $this->assertSame($submission->assignment->title, $notification->data['assignment_title']);
        $this->assertEquals(90, $notification->data['score']);
    }
}
