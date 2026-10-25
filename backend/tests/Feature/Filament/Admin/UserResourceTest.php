<?php

namespace Tests\Feature\Filament\Admin;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_can_list_users(): void
    {
        $student = User::factory()->student()->create();

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$student]);
    }

    public function test_admin_can_create_a_teacher_with_profile(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Ahmed Samir',
                'email' => 'ahmed@example.com',
                'password' => 'secret-password',
                'role' => Role::Teacher->value,
            ])
            ->fillForm([
                'teacherProfile.specialty' => 'Physics',
                'teacherProfile.bio' => 'Physics teacher for secondary school.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $teacher = User::where('email', 'ahmed@example.com')->firstOrFail();

        $this->assertTrue($teacher->isTeacher());
        $this->assertSame('Physics', $teacher->teacherProfile?->specialty);
        $this->assertTrue($teacher->teacherProfile->is_approved);
        $this->assertNull($teacher->studentProfile);
    }

    public function test_role_is_required(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'No Role',
                'email' => 'norole@example.com',
                'password' => 'secret-password',
            ])
            ->call('create')
            ->assertHasFormErrors(['role' => 'required']);
    }

    public function test_editing_keeps_password_when_left_empty(): void
    {
        $student = User::factory()->student()->create(['password' => 'old-password']);
        $hash = $student->password;

        Livewire::test(EditUser::class, ['record' => $student->getRouteKey()])
            ->assertSchemaStateSet(['role' => Role::Student->value])
            ->fillForm(['name' => 'Renamed Student', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $student->refresh();
        $this->assertSame('Renamed Student', $student->name);
        $this->assertSame($hash, $student->password);
        $this->assertTrue($student->isStudent());
    }
}
