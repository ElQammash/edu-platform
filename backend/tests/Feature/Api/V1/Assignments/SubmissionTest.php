<?php

namespace Tests\Feature\Api\V1\Assignments;

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Assignment $assignment;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Submission::DISK);
        $this->assignment = Assignment::factory()->create(['max_score' => 20]);
        $this->student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->assignment->course()->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function submit(array $data, ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->student)
            ->post(route('api.v1.assignments.submit', $this->assignment), $data, ['Accept' => 'application/json']);
    }

    public function test_student_sees_the_assignment_and_no_submission_yet(): void
    {
        $this->actingAs($this->student)
            ->getJson(route('api.v1.assignments.show', $this->assignment))
            ->assertOk()
            ->assertJsonPath('data.title', $this->assignment->title)
            ->assertJsonPath('data.max_score', 20)
            ->assertJsonPath('data.course.slug', $this->assignment->course()->slug)
            ->assertJsonPath('data.submission', null);
    }

    public function test_student_submits_text_only(): void
    {
        $this->submit(['content' => 'My answer'])
            ->assertCreated()
            ->assertJsonPath('data.content', 'My answer')
            ->assertJsonPath('data.file', null)
            ->assertJsonPath('data.is_late', false)
            ->assertJsonPath('data.can_edit', true);
    }

    public function test_student_submits_a_pdf_only(): void
    {
        $response = $this->submit(['file' => UploadedFile::fake()->create('answer.pdf', 500, 'application/pdf')])
            ->assertCreated()
            ->assertJsonPath('data.file.name', 'answer.pdf')
            ->assertJsonPath('data.file.mime', 'application/pdf');

        $path = Submission::sole()->file_path;
        Storage::disk(Submission::DISK)->assertExists((string) $path);
        // The URL points at the authorized endpoint, never at the stored path.
        $this->assertStringNotContainsString((string) $path, (string) $response->json('data.file.url'));
    }

    public function test_student_submits_text_and_an_image(): void
    {
        $this->submit(['content' => 'See photo', 'file' => UploadedFile::fake()->image('board.jpg')])
            ->assertCreated()
            ->assertJsonPath('data.content', 'See photo')
            ->assertJsonPath('data.file.name', 'board.jpg');
    }

    public function test_other_file_types_and_large_files_are_rejected(): void
    {
        $this->submit(['file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->submit(['file' => UploadedFile::fake()->create('notes.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->submit(['file' => UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->assertSame(0, Submission::count());
    }

    public function test_empty_submissions_are_rejected(): void
    {
        $this->submit(['content' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_late_submissions_are_accepted_and_flagged(): void
    {
        $this->assignment->update(['due_at' => now()->subHour()]);

        $this->submit(['content' => 'Sorry, late'])
            ->assertCreated()
            ->assertJsonPath('data.is_late', true);
    }

    public function test_student_edits_their_submission_until_it_is_graded(): void
    {
        $this->submit(['content' => 'First try'])->assertCreated();
        $this->submit(['content' => 'Second try'])->assertOk()->assertJsonPath('data.content', 'Second try');
        $this->assertSame(1, Submission::count());

        Submission::sole()->forceFill(['score' => 15, 'graded_at' => now()])->save();

        $this->submit(['content' => 'After grading'])
            ->assertForbidden()
            ->assertJsonPath('message', __('submissions.already_graded'));
        $this->assertSame('Second try', Submission::sole()->content);
    }

    public function test_graded_submission_shows_score_and_feedback(): void
    {
        Submission::factory()->graded(18)->create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'feedback' => 'Well done',
        ]);

        $this->actingAs($this->student)
            ->getJson(route('api.v1.assignments.show', $this->assignment))
            ->assertJsonPath('data.submission.is_graded', true)
            ->assertJsonPath('data.submission.score', 18)
            ->assertJsonPath('data.submission.feedback', 'Well done')
            ->assertJsonPath('data.submission.can_edit', false);
    }

    public function test_students_who_are_not_enrolled_cannot_see_or_submit(): void
    {
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)->getJson(route('api.v1.assignments.show', $this->assignment))->assertForbidden();
        $this->submit(['content' => 'Hi'], $outsider)->assertForbidden();
    }

    public function test_teachers_cannot_submit(): void
    {
        $this->submit(['content' => 'Hi'], $this->assignment->course()->teacher)->assertForbidden();
    }

    public function test_lesson_shows_its_assignment_only_to_those_who_can_open_it(): void
    {
        /** @var Lesson $lesson */
        $lesson = $this->assignment->lesson;
        $lesson->update(['is_free_preview' => true]);

        $this->actingAs($this->student)
            ->getJson(route('api.v1.lessons.show', $lesson))
            ->assertJsonPath('data.assignment.id', $this->assignment->id);

        // A free-preview visitor sees the lesson but not the assignment.
        $this->actingAs(User::factory()->student()->create())
            ->getJson(route('api.v1.lessons.show', $lesson))
            ->assertOk()
            ->assertJsonMissingPath('data.assignment');
    }
}
