<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Multiple choice by default, with four options of which the first is correct.
 *
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
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
            'type' => QuestionType::Mcq,
            'body' => rtrim(fake()->sentence(6), '.').'?',
            'points' => 2,
            'order' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Question $question): void {
            if ($question->type === QuestionType::Essay || $question->options()->exists()) {
                return;
            }

            $bodies = $question->type === QuestionType::TrueFalse
                ? [QuestionOption::TRUE, QuestionOption::FALSE]
                : [fake()->word(), fake()->word(), fake()->word(), fake()->word()];

            foreach ($bodies as $i => $body) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'body' => $body,
                    'is_correct' => $i === 0,
                    'order' => $i,
                ]);
            }
        });
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => ['type' => QuestionType::TrueFalse]);
    }

    public function essay(int $points = 5): static
    {
        return $this->state(fn (array $attributes) => ['type' => QuestionType::Essay, 'points' => $points]);
    }
}
