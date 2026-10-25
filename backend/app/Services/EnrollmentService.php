<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    /**
     * A student enrolling in a free course. Enrolling twice returns the
     * existing enrollment. Authorization (CoursePolicy::enroll) is the caller's job.
     */
    public function enrollSelf(User $student, Course $course): Enrollment
    {
        return Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            ['payment_status' => PaymentStatus::Free, 'enrolled_at' => now()],
        );
    }

    /**
     * The admin enrolling a student who paid outside the platform.
     *
     * @throws ValidationException when the user is not a student
     */
    public function enrollManually(User $student, Course $course, User $admin): Enrollment
    {
        if (! $student->isStudent()) {
            throw ValidationException::withMessages([
                'student_id' => __('enrollments.not_a_student'),
            ]);
        }

        return Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            [
                'payment_status' => $course->isFree() ? PaymentStatus::Free : PaymentStatus::Manual,
                'enrolled_by' => $admin->id,
                'enrolled_at' => now(),
            ],
        );
    }
}
