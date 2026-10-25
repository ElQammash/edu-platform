<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'grade_level' => fake()->randomElement(['Grade 10', 'Grade 11', 'Grade 12']),
            'parent_phone' => fake()->optional()->numerify('01#########'),
        ];
    }
}
