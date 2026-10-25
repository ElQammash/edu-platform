<?php

namespace App\Services\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentRegistrationService
{
    /**
     * Create a student account with its profile and role in one transaction.
     *
     * @param  array{name: string, email: string, password: string, grade_level?: string|null, parent_phone?: string|null}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole(Role::Student);

            $user->studentProfile()->create([
                'grade_level' => $data['grade_level'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
            ]);

            return $user;
        });
    }
}
