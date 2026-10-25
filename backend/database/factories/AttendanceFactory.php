<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'lesson_id' => Lesson::factory()->live(),
            'attended_at' => now(),
            'source' => Attendance::SOURCE_JOIN,
        ];
    }
}
