<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'instructions' => fake()->paragraph(),
            'due_at' => now()->addWeek(),
            'max_score' => 100,
        ];
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDay(),
        ]);
    }

    public function withoutDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => null,
        ]);
    }
}
