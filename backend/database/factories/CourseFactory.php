<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use App\Enums\Language;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'title' => rtrim(fake()->unique()->sentence(4), '.'),
            'description' => fake()->paragraphs(2, true),
            'price' => 0,
            'language' => Language::Arabic,
            'type' => CourseType::Recorded,
            'status' => CourseStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Draft,
        ]);
    }

    public function paid(float $price = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }
}
