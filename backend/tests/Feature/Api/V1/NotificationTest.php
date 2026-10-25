<?php

namespace Tests\Feature\Api\V1;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionGraded;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        foreach (Submission::factory()->count(2)->create(['student_id' => $this->student->id]) as $submission) {
            app(GradingService::class)->grade($submission, $teacher, 75);
        }
    }

    public function test_student_lists_notifications_with_unread_count(): void
    {
        $this->actingAs($this->student)
            ->getJson(route('api.v1.me.notifications'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', SubmissionGraded::TYPE)
            ->assertJsonPath('data.0.read_at', null)
            ->assertJsonPath('meta.unread_count', 2);
    }

    public function test_mark_one_and_all_as_read(): void
    {
        $id = $this->student->notifications()->first()?->id;

        $this->actingAs($this->student)->postJson(route('api.v1.me.notifications.read', $id))->assertNoContent();
        $this->actingAs($this->student)->getJson(route('api.v1.me.notifications'))->assertJsonPath('meta.unread_count', 1);

        $this->actingAs($this->student)->postJson(route('api.v1.me.notifications.read-all'))->assertNoContent();
        $this->actingAs($this->student)->getJson(route('api.v1.me.notifications'))->assertJsonPath('meta.unread_count', 0);
    }

    public function test_users_cannot_touch_other_peoples_notifications(): void
    {
        $id = $this->student->notifications()->first()?->id;

        $this->actingAs(User::factory()->student()->create())
            ->postJson(route('api.v1.me.notifications.read', $id))
            ->assertNotFound();

        $this->assertSame(2, $this->student->unreadNotifications()->count());
    }
}
