<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Students pay for paid courses they are not enrolled in. A payment is
 * private to its student and admins.
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $payment->student_id === $user->id || $user->isAdmin();
    }

    public function checkout(User $user, Course $course): Response
    {
        if (! $course->isPublished()) {
            return Response::denyAsNotFound();
        }

        if (! $user->isStudent()) {
            return Response::deny(__('payments.errors.students_only'));
        }

        if ($course->isFree()) {
            return Response::deny(__('payments.errors.free'));
        }

        return $user->isEnrolledIn($course)
            ? Response::deny(__('payments.errors.already_enrolled'))
            : Response::allow();
    }

    /**
     * Payments change only through verified gateway messages.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
