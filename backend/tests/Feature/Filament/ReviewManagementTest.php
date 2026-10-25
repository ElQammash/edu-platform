<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Reviews\Pages\ListReviews as AdminListReviews;
use App\Filament\Teacher\Resources\Reviews\Pages\ListReviews as TeacherListReviews;
use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teachers_see_and_reply_to_reviews_of_their_own_courses_only(): void
    {
        $teacher = User::factory()->teacher()->create();
        $mine = Review::factory()->create(['course_id' => Course::factory()->for($teacher, 'teacher')->create()->id]);
        $theirs = Review::factory()->create();
        $this->actingAs($teacher);
        Filament::setCurrentPanel('teacher');

        Livewire::test(TeacherListReviews::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->assertActionDoesNotExist(TestAction::make('hide')->table($mine))
            ->callAction(TestAction::make('reply')->table($mine), ['teacher_reply' => 'Thank you!'])
            ->assertHasNoFormErrors();

        $mine->refresh();
        $this->assertSame('Thank you!', $mine->teacher_reply);
        $this->assertNotNull($mine->teacher_replied_at);
    }

    public function test_admins_hide_a_review_and_the_rating_drops_it(): void
    {
        $course = Course::factory()->create();
        $spam = Review::factory()->create(['course_id' => $course->id, 'rating' => 1]);
        Review::factory()->create(['course_id' => $course->id, 'rating' => 5]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        Livewire::test(AdminListReviews::class)
            ->callAction(TestAction::make('hide')->table($spam));

        $spam->refresh();
        $this->assertTrue($spam->isHidden());
        $this->assertSame($admin->id, $spam->hidden_by);
        $this->assertSame('5.00', $course->fresh()?->rating_avg);

        Livewire::test(AdminListReviews::class)
            ->callAction(TestAction::make('hide')->table($spam));

        $this->assertFalse($spam->fresh()?->isHidden());
        $this->assertSame('3.00', $course->fresh()?->rating_avg);
    }
}
