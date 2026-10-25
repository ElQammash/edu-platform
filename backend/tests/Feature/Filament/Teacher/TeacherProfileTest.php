<?php

namespace Tests\Feature\Filament\Teacher;

use App\Filament\Teacher\Pages\EditTeacherProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create(['email' => 'teacher@example.com']);
        $this->actingAs($this->teacher);
        Filament::setCurrentPanel('teacher');
    }

    public function test_profile_page_is_reachable(): void
    {
        $this->get('/teacher/profile')->assertOk();
    }

    public function test_teacher_updates_name_and_public_profile(): void
    {
        Livewire::test(EditTeacherProfile::class)
            ->assertSchemaStateSet(['specialty' => $this->teacher->teacherProfile?->specialty])
            ->fillForm([
                'name' => 'Dr. Ahmed Samir',
                'specialty' => 'Physics and astronomy',
                'bio' => 'Twelve years teaching secondary physics.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->teacher->refresh();
        $this->assertSame('Dr. Ahmed Samir', $this->teacher->name);
        $this->assertSame('Physics and astronomy', $this->teacher->teacherProfile?->specialty);
        $this->assertSame('Twelve years teaching secondary physics.', $this->teacher->teacherProfile?->bio);
        // Approval is the admin's decision and is untouched.
        $this->assertTrue($this->teacher->teacherProfile?->is_approved);
    }

    public function test_teacher_cannot_change_their_email(): void
    {
        Livewire::test(EditTeacherProfile::class)
            ->fillForm(['email' => 'other@example.com'])
            ->call('save');

        $this->assertSame('teacher@example.com', $this->teacher->refresh()->email);
    }
}
