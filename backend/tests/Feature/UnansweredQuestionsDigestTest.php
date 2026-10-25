<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Section;
use App\Models\User;
use App\Notifications\UnansweredQuestionsDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UnansweredQuestionsDigestTest extends TestCase
{
    use RefreshDatabase;

    private function lessonOf(User $teacher): Lesson
    {
        return Lesson::factory()->for(Section::factory()->for(Course::factory()->for($teacher, 'teacher')))->create();
    }

    public function test_teachers_with_unanswered_questions_get_one_digest_a_day(): void
    {
        Notification::fake();
        $busy = User::factory()->teacher()->create();
        $idle = User::factory()->teacher()->create();
        $lesson = $this->lessonOf($busy);
        LessonComment::factory()->count(2)->create(['lesson_id' => $lesson->id]);
        $answered = LessonComment::factory()->create(['lesson_id' => $lesson->id]);
        LessonComment::factory()->replyTo($answered)->byStaff($busy)->create();
        LessonComment::factory()->resolved()->create(['lesson_id' => $this->lessonOf($idle)->id]);

        $this->artisan('qa:send-teacher-digest')->assertSuccessful();
        $this->artisan('qa:send-teacher-digest')->assertSuccessful();

        Notification::assertSentToTimes($busy, UnansweredQuestionsDigest::class, 1);
        Notification::assertSentTo($busy, UnansweredQuestionsDigest::class, function (UnansweredQuestionsDigest $digest) use ($busy): bool {
            return $digest->total === 2
                && count($digest->questions) === 2
                && $digest->via($busy) === ['mail']
                && str_contains($digest->panelUrl, '/teacher/questions');
        });
        Notification::assertNotSentTo($idle, UnansweredQuestionsDigest::class);
    }

    public function test_the_digest_renders_in_the_teachers_language(): void
    {
        $teacher = User::factory()->teacher()->create(['locale' => 'ar']);
        $digest = new UnansweredQuestionsDigest([[
            'student' => 'Mona',
            'lesson' => 'Ohm',
            'course' => 'Physics',
            'excerpt' => 'Why?',
            'asked_at' => now()->subHours(3)->toIso8601String(),
        ]], 12, 'https://example.test/teacher/questions');

        app()->setLocale('ar');
        $mail = $digest->toMail($teacher);

        $this->assertSame('12 سؤالاً في انتظار ردك', $mail->subject);
        $this->assertStringContainsString('Mona', implode(' ', $mail->introLines));
        $this->assertStringContainsString('11', implode(' ', $mail->introLines));
    }

    public function test_the_digest_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'qa:send-teacher-digest'));

        $this->assertCount(1, $events);
        $this->assertSame('0 8 * * *', $events->first()->expression);
    }
}
