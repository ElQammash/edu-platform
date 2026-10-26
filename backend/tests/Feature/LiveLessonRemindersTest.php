<?php

namespace Tests\Feature;

use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Notifications\LiveLessonChanged;
use App\Notifications\LiveLessonReminder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LiveLessonRemindersTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $this->student = User::factory()->student()->create();
        Enrollment::factory()->create(['student_id' => $this->student->id, 'course_id' => $this->course->id]);
    }

    private function liveLesson(\DateTimeInterface $at): Lesson
    {
        return Lesson::factory()->for(Section::factory()->for($this->course))->live($at)->create();
    }

    public function test_reminders_go_out_24_hours_and_1_hour_before_exactly_once(): void
    {
        Notification::fake();
        $this->liveLesson(now()->addHours(30));

        $this->artisan('lessons:send-live-reminders')->assertSuccessful();
        Notification::assertNothingSent();

        $this->travel(7)->hours(); // 23 hours before
        $this->artisan('lessons:send-live-reminders');
        $this->artisan('lessons:send-live-reminders');
        Notification::assertSentToTimes($this->student, LiveLessonReminder::class, 1);
        Notification::assertSentTo($this->student, LiveLessonReminder::class, fn (LiveLessonReminder $n) => $n->kind === LiveLessonReminder::KIND_DAY);

        $this->travel(22 * 60 + 30)->minutes(); // 30 minutes before
        $this->artisan('lessons:send-live-reminders');
        $this->artisan('lessons:send-live-reminders');
        Notification::assertSentToTimes($this->student, LiveLessonReminder::class, 2);
        Notification::assertSentTo($this->student, LiveLessonReminder::class, fn (LiveLessonReminder $n) => $n->kind === LiveLessonReminder::KIND_HOUR);
    }

    public function test_a_lesson_less_than_an_hour_away_gets_only_the_1_hour_reminder(): void
    {
        Notification::fake();
        $this->liveLesson(now()->addMinutes(40));

        $this->artisan('lessons:send-live-reminders');

        Notification::assertSentToTimes($this->student, LiveLessonReminder::class, 1);
        Notification::assertSentTo($this->student, LiveLessonReminder::class, fn (LiveLessonReminder $n) => $n->kind === LiveLessonReminder::KIND_HOUR);
    }

    public function test_students_of_other_courses_and_draft_courses_get_nothing(): void
    {
        Notification::fake();
        $other = User::factory()->student()->create();
        $this->liveLesson(now()->addMinutes(30));
        $this->course->update(['status' => 'draft']);

        $this->artisan('lessons:send-live-reminders');

        Notification::assertNothingSent();
        Notification::assertNotSentTo($other, LiveLessonReminder::class);
    }

    public function test_rescheduling_notifies_students_and_re_arms_the_reminders(): void
    {
        Notification::fake();
        $lesson = $this->liveLesson(now()->addMinutes(30));
        $this->artisan('lessons:send-live-reminders');

        $lesson->update(['live_at' => now()->addMinutes(50)]);
        Notification::assertSentTo($this->student, LiveLessonChanged::class, fn (LiveLessonChanged $n) => $n->type === LiveLessonChanged::RESCHEDULED);

        $this->artisan('lessons:send-live-reminders');
        Notification::assertSentToTimes($this->student, LiveLessonReminder::class, 2);
    }

    public function test_cancelling_an_upcoming_lesson_notifies_students(): void
    {
        Notification::fake();
        $lesson = $this->liveLesson(now()->addDay());
        $lesson->update(['type' => LessonType::Video, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        Notification::assertSentTo($this->student, LiveLessonChanged::class, fn (LiveLessonChanged $n) => $n->type === LiveLessonChanged::CANCELLED);
    }

    public function test_deleting_an_upcoming_lesson_notifies_students(): void
    {
        Notification::fake();
        $this->liveLesson(now()->addDay())->delete();

        Notification::assertSentTo($this->student, LiveLessonChanged::class, fn (LiveLessonChanged $n) => $n->type === LiveLessonChanged::CANCELLED);
    }

    public function test_past_lessons_changing_notify_nobody(): void
    {
        Notification::fake();
        $this->liveLesson(now()->subDay())->update(['live_at' => now()->subDays(2)]);

        Notification::assertNothingSent();
    }

    public function test_reminders_are_queued_not_sent_inline(): void
    {
        Queue::fake();
        $this->liveLesson(now()->addMinutes(30));

        $this->artisan('lessons:send-live-reminders');

        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_the_reminder_email_is_in_the_students_language(): void
    {
        $lesson = $this->liveLesson(now()->addMinutes(30));
        $this->student->forceFill(['locale' => 'en'])->save();

        $mail = (new LiveLessonReminder($lesson, LiveLessonReminder::KIND_HOUR))->toMail($this->student);
        $this->assertStringContainsString('In 1 hour', (string) $mail->subject);

        app()->setLocale('ar');
        $mail = (new LiveLessonReminder($lesson, LiveLessonReminder::KIND_HOUR))->toMail($this->student);
        $this->assertStringContainsString('كمان ساعة', (string) $mail->subject);
    }

    public function test_the_scheduler_runs_reminders_and_the_queue_worker(): void
    {
        // Read the events, not schedule:list, whose output squeezes long commands.
        $commands = collect(app(Schedule::class)->events())->map(fn (Event $event): string => (string) $event->command)->values();

        $reminders = $commands->search(fn (string $c): bool => str_contains($c, 'lessons:send-live-reminders'));
        $worker = $commands->search(fn (string $c): bool => str_contains($c, 'queue:work --stop-when-empty'));
        $this->assertIsInt($reminders);
        $this->assertIsInt($worker);
        // Reminders are queued before the worker runs, so they go out in the same minute.
        $this->assertLessThan($worker, $reminders);
    }
}
