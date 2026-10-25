<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
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
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->sentence(),
            'time_limit_minutes' => 15,
            'pass_score' => 50,
            'max_attempts' => 1,
            'due_at' => null,
            'show_answers' => null,
            'shuffle' => false,
        ];
    }

    public function untimed(): static
    {
        return $this->state(fn (array $attributes) => ['time_limit_minutes' => null]);
    }

    public function dueIn(int $days): static
    {
        return $this->state(fn (array $attributes) => ['due_at' => now()->addDays($days)]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => ['due_at' => now()->subDay()]);
    }
}
