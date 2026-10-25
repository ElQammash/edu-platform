<?php

namespace Tests\Feature\Api\V1\Assignments;

use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFileTest extends TestCase
{
    use RefreshDatabase;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Submission::DISK);
        $original = Submission::factory()->create();
        $this->submission = app(SubmissionService::class)->submit(
            $original->student,
            $original->assignment,
            null,
            UploadedFile::fake()->create('answer.pdf', 50, 'application/pdf'),
        );
    }

    public function test_the_student_can_open_their_file(): void
    {
        $this->actingAs($this->submission->student)
            ->get(route('api.v1.submissions.file', $this->submission))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_the_course_teacher_and_admins_can_open_it_from_the_panels(): void
    {
        $this->actingAs($this->submission->assignment->course()->teacher)
            ->get(route('submissions.file', $this->submission))
            ->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('submissions.file', $this->submission))
            ->assertOk();
    }

    public function test_other_students_teachers_and_guests_cannot(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('api.v1.submissions.file', $this->submission))
            ->assertForbidden();

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('submissions.file', $this->submission))
            ->assertForbidden();
    }

    public function test_guests_cannot(): void
    {
        $this->get(route('submissions.file', $this->submission))->assertForbidden();
        $this->getJson(route('api.v1.submissions.file', $this->submission))->assertUnauthorized();
    }

    public function test_the_file_is_not_reachable_on_a_public_url(): void
    {
        $path = (string) $this->submission->file_path;

        $this->get('/storage/'.$path)->assertNotFound();
        $this->assertFalse(Storage::disk('public')->exists($path));
    }
}
