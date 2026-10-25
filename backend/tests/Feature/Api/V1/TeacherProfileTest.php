<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Role;
use App\Models\Course;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_profile_shows_bio_and_published_courses_only(): void
    {
        $teacher = User::factory()->teacher()->create();
        $published = Course::factory()->for($teacher, 'teacher')->create();
        Course::factory()->for($teacher, 'teacher')->draft()->create();

        $this->getJson(route('api.v1.teachers.show', $teacher))
            ->assertOk()
            ->assertJsonPath('data.name', $teacher->name)
            ->assertJsonPath('data.bio', $teacher->teacherProfile?->bio)
            ->assertJsonPath('data.courses_count', 1)
            ->assertJsonCount(1, 'data.courses')
            ->assertJsonPath('data.courses.0.slug', $published->slug)
            ->assertJsonMissingPath('data.email');
    }

    public function test_students_admins_and_unapproved_teachers_are_not_found(): void
    {
        $pending = User::factory()->withRole(Role::Teacher)
            ->has(TeacherProfile::factory()->pending(), 'teacherProfile')
            ->create();

        $this->getJson(route('api.v1.teachers.show', User::factory()->student()->create()))->assertNotFound();
        $this->getJson(route('api.v1.teachers.show', User::factory()->admin()->create()))->assertNotFound();
        $this->getJson(route('api.v1.teachers.show', $pending))->assertNotFound();
    }
}
