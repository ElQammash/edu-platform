<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateStudentProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user->load(['roles', 'studentProfile', 'teacherProfile']));
    }

    /**
     * POST /me/profile (multipart): name, grade_level, parent_phone, avatar, remove_avatar.
     */
    public function update(UpdateStudentProfileRequest $request, StudentProfileService $profiles): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{name: string, grade_level?: string|null, parent_phone?: string|null} $data */
        $data = $request->safe()->only(['name', 'grade_level', 'parent_phone']);

        $user = $profiles->update($user, $data, $request->file('avatar'), $request->boolean('remove_avatar'));

        return new UserResource($user);
    }
}
