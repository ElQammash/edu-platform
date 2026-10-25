<?php

namespace Tests\Feature\Filament\Admin;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Courses\Pages\CreateCourse;
use App\Filament\Admin\Resources\Courses\Pages\EditCourse;
use App\Filament\Admin\Resources\Courses\Pages\ListCourses;
use App\Filament\Admin\Resources\Courses\RelationManagers\EnrollmentsRelationManager;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel('admin');
    }

    public function test_admin_sees_every_course(): void
    {
        $courses = Course::factory()->count(2)->create();

        Livewire::test(ListCourses::class)->assertCanSeeTableRecords($courses);
    }

    public function test_admin_creates_a_course_for_a_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();

        Livewire::test(CreateCourse::class)
            ->fillForm([
                'teacher_id' => $teacher->id,
                'title' => 'Chemistry',
                'price' => 600,
                'language' => 'en',
                'type' => 'hybrid',
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($teacher->id, Course::sole()->teacher_id);
    }

    public function test_teacher_is_required_in_the_admin_panel(): void
    {
        Livewire::test(CreateCourse::class)
            ->fillForm(['title' => 'No teacher', 'price' => 0, 'language' => 'ar', 'type' => 'recorded', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['teacher_id' => 'required']);
    }

    public function test_admin_enrolls_a_student_manually_in_a_paid_course(): void
    {
        $course = Course::factory()->paid()->create();
        $student = User::factory()->student()->create();

        Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('create')->table(), ['student_id' => $student->id])
            ->assertHasNoFormErrors();

        $enrollment = Enrollment::sole();
        $this->assertSame($student->id, $enrollment->student_id);
        $this->assertSame(PaymentStatus::Manual, $enrollment->payment_status);
        $this->assertSame($this->admin->id, $enrollment->enrolled_by);
    }

    public function test_enrollments_list_shows_the_course_students(): void
    {
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->for($course)->create();
        $other = Enrollment::factory()->create();

        Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->assertCanSeeTableRecords([$enrollment])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
