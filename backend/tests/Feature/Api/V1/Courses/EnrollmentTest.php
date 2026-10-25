<?php

namespace Tests\Feature\Api\V1\Courses;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_enroll_in_a_free_course(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();

        $this->actingAs($student)
            ->postJson(route('api.v1.courses.enroll', $course))
            ->assertCreated()
            ->assertJsonPath('data.payment_status', PaymentStatus::Free->value)
            ->assertJsonPath('data.progress_percent', 0);

        $this->assertTrue($student->isEnrolledIn($course));
    }

    public function test_enrolling_twice_keeps_one_enrollment(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->create();

        $this->actingAs($student)->postJson(route('api.v1.courses.enroll', $course))->assertCreated();
        $this->actingAs($student)->postJson(route('api.v1.courses.enroll', $course))->assertOk();

        $this->assertSame(1, Enrollment::count());
    }

    public function test_paid_course_cannot_be_self_enrolled(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->paid()->create();

        $this->actingAs($student)
            ->postJson(route('api.v1.courses.enroll', $course))
            ->assertForbidden()
            ->assertJsonPath('message', __('enrollments.paid_course'));

        $this->assertFalse($student->isEnrolledIn($course));
    }

    public function test_draft_course_cannot_be_enrolled(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->postJson(route('api.v1.courses.enroll', Course::factory()->draft()->create()))
            ->assertForbidden();
    }

    public function test_teachers_cannot_enroll(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->postJson(route('api.v1.courses.enroll', Course::factory()->create()))
            ->assertForbidden();
    }

    public function test_guests_must_log_in_to_enroll(): void
    {
        $this->postJson(route('api.v1.courses.enroll', Course::factory()->create()))->assertUnauthorized();
    }

    public function test_my_courses_lists_only_the_students_enrollments(): void
    {
        $student = User::factory()->student()->create();
        $mine = Enrollment::factory()->create(['student_id' => $student->id, 'progress_percent' => 40]);
        Enrollment::factory()->create();

        $this->actingAs($student)
            ->getJson(route('api.v1.me.courses'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.progress_percent', 40)
            ->assertJsonPath('data.0.course.slug', $mine->course->slug)
            ->assertJsonPath('data.0.course.lessons_count', 0);
    }
}
