<?php

namespace Tests\Feature\Api\V1\Assignments;

use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_the_api_hands_out_a_working_signed_link(): void
    {
        Enrollment::factory()->create([
            'student_id' => $this->submission->student_id,
            'course_id' => $this->submission->assignment->course()->id,
        ]);

        $url = $this->actingAs($this->submission->student)
            ->getJson(route('api.v1.assignments.show', $this->submission->assignment))
            ->json('data.submission.file.url');

        $this->assertIsString($url);

        // Opened in a new tab: no session needed, the signature authorizes it.
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_signed_links_expire_and_cannot_be_altered(): void
    {
        $url = URL::temporarySignedRoute('submissions.file.signed', now()->addMinutes(30), ['submission' => $this->submission->id]);

        // Pointing the link at someone else's submission breaks the signature.
        $other = Submission::factory()->create();
        $this->get(str_replace('/'.$this->submission->id.'/', '/'.$other->id.'/', $url))->assertForbidden();

        $this->travel(31)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_the_file_is_not_reachable_on_a_public_url(): void
    {
        $path = (string) $this->submission->file_path;

        $this->get('/storage/'.$path)->assertNotFound();
        $this->assertFalse(Storage::disk('public')->exists($path));
    }
}
