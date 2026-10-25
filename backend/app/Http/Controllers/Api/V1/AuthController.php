<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\Auth\StudentRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Cookie-based (Sanctum SPA) authentication for the React app.
 *
 * The client must call GET /sanctum/csrf-cookie first, then send the
 * X-XSRF-TOKEN header with every state-changing request.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request, StudentRegistrationService $registration): JsonResponse
    {
        /** @var array{name: string, email: string, password: string, grade_level?: string|null, parent_phone?: string|null} $data */
        $data = $request->validated();

        $user = $registration->register($data);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $this->userResource($user)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): UserResource
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $this->userResource($user);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return $this->userResource($user);
    }

    private function userResource(User $user): UserResource
    {
        return new UserResource($user->load(['roles', 'studentProfile', 'teacherProfile']));
    }
}
