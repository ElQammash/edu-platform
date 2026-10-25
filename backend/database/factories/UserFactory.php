<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->withRole(Role::Admin);
    }

    public function teacher(): static
    {
        return $this->withRole(Role::Teacher)
            ->has(TeacherProfile::factory(), 'teacherProfile');
    }

    public function student(): static
    {
        return $this->withRole(Role::Student)
            ->has(StudentProfile::factory(), 'studentProfile');
    }

    /**
     * Assign a role after creation, creating the role if it was not seeded yet.
     */
    public function withRole(Role $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            RoleModel::findOrCreate($role->value, 'web');
            $user->assignRole($role);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
