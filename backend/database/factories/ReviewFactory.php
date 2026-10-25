<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'student_id' => User::factory()->student(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->optional(0.8)->sentence(14),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => ['hidden_at' => now()]);
    }
}
