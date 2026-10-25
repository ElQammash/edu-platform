<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The README promises a working demo after `migrate --seed`.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeds_and_can_be_seeded_again(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, User::count());
        $this->assertSame(5, Course::count());
        $this->assertSame(4, Course::published()->count());
        $this->assertSame(5, Enrollment::count());
        $this->assertSame(50, Enrollment::whereHas('student', fn ($q) => $q->where('email', 'student1@edu-platform.test'))
            ->whereHas('course', fn ($q) => $q->where('slug', 'physics-secondary-3'))
            ->value('progress_percent'));
    }
}
