<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    /**
     * POST /courses/{slug}/checkout: where to send the student to pay.
     * Reuses their open checkout for the course if there is one.
     */
    public function checkout(Request $request, Course $course, PaymentService $payments): JsonResponse
    {
        Gate::authorize('checkout', [Payment::class, $course]);

        /** @var User $user */
        $user = $request->user();
        $payment = $payments->checkout($user, $course)->load('course');

        return response()->json(['data' => [
            ...(new PaymentResource($payment))->toArray($request),
            'checkout_url' => $payment->checkout_url,
        ]], $payment->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /payments/{reference}: the result page polls this until the
     * gateway confirms.
     */
    public function show(Payment $payment): PaymentResource
    {
        Gate::authorize('view', $payment);

        return new PaymentResource($payment->load('course'));
    }

    /**
     * GET /me/payments: the student's payments, newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return PaymentResource::collection($user->payments()->with('course')->latest()->latest('id')->paginate(20));
    }
}
