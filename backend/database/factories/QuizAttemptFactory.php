<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A bare attempt. Tests that need real question order and grading should
 * go through QuizAttemptService instead.
 *
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'student_id' => User::factory()->student(),
            'started_at' => now(),
            'expires_at' => null,
            'max_score' => 10,
            'question_order' => [],
            'option_order' => [],
        ];
    }
}
