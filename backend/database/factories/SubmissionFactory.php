<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'student_id' => User::factory()->student(),
            'content' => fake()->paragraph(),
            'submitted_at' => now(),
        ];
    }

    public function graded(float $score = 80, ?User $grader = null): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'feedback' => fake()->sentence(),
            'graded_by' => $grader?->id,
            'graded_at' => now(),
        ]);
    }
}
