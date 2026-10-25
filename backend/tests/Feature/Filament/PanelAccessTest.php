<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_admin_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk();
    }

    public function test_teachers_and_students_cannot_open_admin_panel(): void
    {
        $this->actingAs(User::factory()->teacher()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->student()->create())->get('/admin')->assertForbidden();
    }

    public function test_approved_teacher_can_open_teacher_panel(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get('/teacher')
            ->assertOk();
    }

    public function test_unapproved_teacher_cannot_open_teacher_panel(): void
    {
        $teacher = User::factory()->withRole(Role::Teacher)
            ->has(TeacherProfile::factory()->pending(), 'teacherProfile')
            ->create();

        $this->actingAs($teacher)->get('/teacher')->assertForbidden();
    }

    public function test_students_and_admins_cannot_open_teacher_panel(): void
    {
        $this->actingAs(User::factory()->student()->create())->get('/teacher')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/teacher')->assertForbidden();
    }

    public function test_guests_are_redirected_to_panel_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/teacher')->assertRedirect('/teacher/login');
    }
}
