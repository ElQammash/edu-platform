<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonComment>
 */
class LessonCommentFactory extends Factory
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
            'user_id' => User::factory()->student(),
            'body' => fake()->sentence(12),
        ];
    }

    /**
     * A reply in the thread of the given question.
     */
    public function replyTo(LessonComment $question): static
    {
        return $this->state(fn (): array => [
            'lesson_id' => $question->lesson_id,
            'parent_id' => $question->id,
        ]);
    }

    public function byStaff(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'by_staff' => true,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => ['resolved_at' => now()]);
    }
}
