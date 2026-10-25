<?php

namespace Tests\Feature\Api\V1\Courses;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->course = Course::factory()->for($this->teacher, 'teacher')->create(['status' => CourseStatus::Published]);
    }

    private function student(int $progress = 50, ?Course $course = null): User
    {
        $student = User::factory()->student()->create(['name' => 'Mona Adel']);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'course_id' => ($course ?? $this->course)->id,
            'progress_percent' => $progress,
        ]);

        return $student;
    }

    public function test_a_student_past_30_percent_writes_then_rewrites_a_review(): void
    {
        $student = $this->student(30);

        $this->actingAs($student)
            ->putJson(route('api.v1.courses.review.save', $this->course), ['rating' => 4, 'comment' => '  Clear explanations  '])
            ->assertCreated()
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'Clear explanations')
            ->assertJsonPath('data.student.name', 'Mona')
            ->assertJsonPath('data.is_mine', true);

        $this->actingAs($student)
            ->putJson(route('api.v1.courses.review.save', $this->course), ['rating' => 5, 'comment' => ''])
            ->assertOk()
            ->assertJsonPath('data.comment', null);

        $this->assertSame(1, Review::count());
        $this->assertSame('5.00', $this->course->fresh()?->rating_avg);
    }

    public function test_reviewing_needs_enrollment_and_progress(): void
    {
        $this->actingAs($this->student(29))
            ->putJson(route('api.v1.courses.review.save', $this->course), ['rating' => 5])
            ->assertForbidden()
            ->assertJsonPath('message', __('reviews.errors.too_early', ['percent' => 30]));

        $this->actingAs(User::factory()->student()->create())
            ->putJson(route('api.v1.courses.review.save', $this->course), ['rating' => 5])
            ->assertForbidden()
            ->assertJsonPath('message', __('reviews.errors.not_enrolled'));

        $this->actingAs($this->teacher)
            ->putJson(route('api.v1.courses.review.save', $this->course), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_my_review_says_whether_i_can_review_and_why_not(): void
    {
        $this->actingAs($this->student(10))
            ->getJson(route('api.v1.courses.review', $this->course))
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('meta.can_review', false)
            ->assertJsonPath('meta.reason', __('reviews.errors.too_early', ['percent' => 30]))
            ->assertJsonPath('meta.min_progress', 30);

        $student = $this->student(80);
        Review::factory()->hidden()->create(['course_id' => $this->course->id, 'student_id' => $student->id, 'rating' => 1]);

        $this->actingAs($student)
            ->getJson(route('api.v1.courses.review', $this->course))
            ->assertJsonPath('data.rating', 1)
            ->assertJsonPath('data.is_hidden', true)
            ->assertJsonPath('meta.can_review', true);
    }

    public function test_the_public_list_shows_visible_reviews_with_a_breakdown(): void
    {
        Review::factory()->create(['course_id' => $this->course->id, 'rating' => 5]);
        Review::factory()->create(['course_id' => $this->course->id, 'rating' => 5]);
        Review::factory()->create(['course_id' => $this->course->id, 'rating' => 2, 'teacher_reply' => 'Thanks, we will improve.']);
        Review::factory()->hidden()->create(['course_id' => $this->course->id, 'rating' => 1]);

        $this->getJson(route('api.v1.courses.reviews', $this->course))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.summary.count', 3)
            ->assertJsonPath('meta.summary.average', 4)
            ->assertJsonPath('meta.summary.breakdown', ['5' => 2, '4' => 0, '3' => 0, '2' => 1, '1' => 0])
            ->assertJsonFragment(['teacher_reply' => 'Thanks, we will improve.']);
    }

    public function test_hiding_and_deleting_recalculate_course_and_teacher_ratings(): void
    {
        $other = Course::factory()->for($this->teacher, 'teacher')->create(['status' => CourseStatus::Published]);
        $low = Review::factory()->create(['course_id' => $this->course->id, 'rating' => 1]);
        Review::factory()->create(['course_id' => $this->course->id, 'rating' => 5]);
        Review::factory()->create(['course_id' => $other->id, 'rating' => 3]);

        $this->assertSame('3.00', $this->course->fresh()?->rating_avg);
        $this->assertSame('3.00', $this->teacher->teacherProfile()->firstOrFail()->rating);

        $low->update(['hidden_at' => now()]);
        $this->assertSame('5.00', $this->course->fresh()?->rating_avg);
        $this->assertSame(1, $this->course->fresh()?->reviews_count);
        $this->assertSame('4.00', $this->teacher->teacherProfile()->firstOrFail()->rating);

        Review::query()->where('course_id', $other->id)->firstOrFail()->delete();
        $this->assertSame('5.00', $this->teacher->teacherProfile()->firstOrFail()->rating);
    }

    public function test_a_student_deletes_their_review(): void
    {
        $student = $this->student();
        Review::factory()->create(['course_id' => $this->course->id, 'student_id' => $student->id, 'rating' => 2]);

        $this->actingAs($student)->deleteJson(route('api.v1.courses.review.destroy', $this->course))->assertNoContent();

        $this->assertSame(0, Review::count());
        $this->assertSame(0, $this->course->fresh()?->reviews_count);
    }

    public function test_courses_carry_their_rating_and_sort_by_it(): void
    {
        $best = Course::factory()->create(['status' => CourseStatus::Published, 'published_at' => now()->subYear()]);
        Review::factory()->create(['course_id' => $best->id, 'rating' => 5]);
        Review::factory()->create(['course_id' => $this->course->id, 'rating' => 3]);

        $this->getJson(route('api.v1.courses.index', ['sort' => 'rating']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $best->id)
            ->assertJsonPath('data.0.rating.average', 5)
            ->assertJsonPath('data.0.rating.count', 1)
            ->assertJsonPath('data.1.id', $this->course->id);
    }

    public function test_rating_is_validated(): void
    {
        $this->actingAs($this->student())
            ->putJson(route('api.v1.courses.review.save', $this->course), ['rating' => 6, 'comment' => str_repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'comment']);
    }
}
