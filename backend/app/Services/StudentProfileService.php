<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentProfileService
{
    /**
     * Update the student's name and profile. A new avatar replaces the old
     * file; $removeAvatar deletes it without uploading another.
     *
     * @param  array{name: string, grade_level?: string|null, parent_phone?: string|null}  $data
     */
    public function update(User $student, array $data, ?UploadedFile $avatar = null, bool $removeAvatar = false): User
    {
        $disk = Storage::disk('public');

        /** @var StudentProfile $profile */
        $profile = $student->studentProfile()->firstOrNew();
        $oldAvatar = $profile->avatar;

        if ($avatar) {
            $profile->avatar = $avatar->store('avatars', 'public') ?: null;
        } elseif ($removeAvatar) {
            $profile->avatar = null;
        }

        DB::transaction(function () use ($student, $profile, $data) {
            $student->update(['name' => $data['name']]);

            $profile->grade_level = $data['grade_level'] ?? null;
            $profile->parent_phone = $data['parent_phone'] ?? null;
            $profile->save();
        });

        // Only delete the previous file once the new state is saved.
        if ($oldAvatar && $oldAvatar !== $profile->avatar) {
            $disk->delete($oldAvatar);
        }

        return $student->load(['roles', 'studentProfile']);
    }
}
