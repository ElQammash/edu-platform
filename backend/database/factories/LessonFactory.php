<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'type' => LessonType::Video,
            'content' => fake()->paragraph(),
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_seconds' => 600,
            'order' => 0,
            'is_free_preview' => false,
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LessonType::Text,
            'content' => fake()->paragraphs(3, true),
            'video_url' => null,
            'duration_seconds' => null,
        ]);
    }

    /**
     * A live session starting at $startsAt (default: tomorrow) for an hour.
     */
    public function live(?\DateTimeInterface $startsAt = null, int $minutes = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LessonType::Live,
            'video_url' => null,
            'live_at' => $startsAt ?? now()->addDay(),
            'duration_seconds' => $minutes * 60,
            'meeting_url' => 'https://us02web.zoom.us/j/1234567890',
        ]);
    }

    public function freePreview(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free_preview' => true,
        ]);
    }
}
