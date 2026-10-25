<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_student_can_view_their_profile(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->getJson(route('api.v1.me.profile'))
            ->assertOk()
            ->assertJsonPath('data.email', $student->email)
            ->assertJsonPath('data.student_profile.grade_level', $student->studentProfile?->grade_level);
    }

    public function test_student_can_update_profile_and_avatar(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)
            ->post(route('api.v1.me.profile.update'), [
                'name' => 'New Name',
                'grade_level' => 'Grade 12',
                'parent_phone' => '01112345678',
                'avatar' => UploadedFile::fake()->image('me.jpg', 200, 200),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.student_profile.grade_level', 'Grade 12')
            ->assertJsonPath('data.student_profile.parent_phone', '01112345678');

        $path = $student->studentProfile()->value('avatar');
        $this->assertIsString($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith($path, $response->json('data.student_profile.avatar_url'));
    }

    public function test_new_avatar_replaces_and_deletes_the_old_one(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student);

        $this->post(route('api.v1.me.profile.update'), ['name' => 'A', 'avatar' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertOk();
        $first = $student->studentProfile()->value('avatar');

        $this->post(route('api.v1.me.profile.update'), ['name' => 'A', 'avatar' => UploadedFile::fake()->image('b.jpg')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($first);

        $this->post(route('api.v1.me.profile.update'), ['name' => 'A', 'remove_avatar' => true], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.student_profile.avatar_url', null);
    }

    public function test_profile_update_is_validated(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->post(route('api.v1.me.profile.update'), [
                'name' => '',
                'parent_phone' => '123',
                'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'parent_phone', 'avatar']);
    }

    public function test_only_students_can_update_their_profile_here(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->postJson(route('api.v1.me.profile.update'), ['name' => 'X'])
            ->assertForbidden();
    }

    public function test_guests_cannot_see_or_update_a_profile(): void
    {
        $this->getJson(route('api.v1.me.profile'))->assertUnauthorized();
        $this->postJson(route('api.v1.me.profile.update'), ['name' => 'X'])->assertUnauthorized();
    }
}
