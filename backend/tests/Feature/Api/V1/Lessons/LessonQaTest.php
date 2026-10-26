<?php

namespace Tests\Feature\Api\V1\Lessons;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Section;
use App\Models\User;
use App\Notifications\LessonAnswerAccepted;
use App\Notifications\LessonQuestionAsked;
use App\Notifications\LessonQuestionReplied;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LessonQaTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Lesson $lesson;

    private User $asker;

    private User $classmate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($this->teacher, 'teacher')->create();
        $this->lesson = Lesson::factory()->for(Section::factory()->for($course))->create();
        $this->asker = $this->enrolledStudent($course);
        $this->classmate = $this->enrolledStudent($course);
    }

    private function enrolledStudent(Course $course): User
    {
        $student = User::factory()->student()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        return $student;
    }

    private function question(): LessonComment
    {
        return LessonComment::factory()->create(['lesson_id' => $this->lesson->id, 'user_id' => $this->asker->id]);
    }

    public function test_an_enrolled_student_asks_and_the_teacher_is_notified_in_app_only(): void
    {
        Notification::fake();

        $this->actingAs($this->asker)
            ->postJson(route('api.v1.lessons.comments.store', $this->lesson), ['body' => 'Why is the current constant?'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Why is the current constant?')
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.by_staff', false)
            ->assertJsonPath('data.can.edit', true)
            ->assertJsonPath('data.replies', []);

        Notification::assertSentTo($this->teacher, LessonQuestionAsked::class, function (LessonQuestionAsked $n): bool {
            return $n->via($this->teacher) === ['database'];
        });
    }

    public function test_staff_get_panel_notifications_and_students_get_app_data(): void
    {
        $this->teacher->update(['locale' => 'ar']);

        $this->actingAs($this->asker)
            ->postJson(route('api.v1.lessons.comments.store', $this->lesson), ['body' => 'Why is the current constant?'])
            ->assertCreated();
        $question = LessonComment::query()->firstOrFail();
        $this->actingAs($this->teacher)
            ->postJson(route('api.v1.comments.reply', $question), ['body' => 'Because R is fixed.'])
            ->assertCreated();

        // Panel format, in the teacher's language, so Filament's bell lists it.
        $forTeacher = $this->teacher->notifications()->firstOrFail();
        $this->assertSame('filament', $forTeacher->data['format']);
        $this->assertStringContainsString('سؤال جديد من', $forTeacher->data['title']);
        $this->assertStringContainsString('/teacher/questions', $forTeacher->data['actions'][0]['url']);

        // The app's own format for the student's React bell.
        $forStudent = $this->asker->notifications()->firstOrFail();
        $this->assertSame(LessonQuestionReplied::TYPE, $forStudent->type);
        $this->assertSame($question->id, $forStudent->data['question_id']);
        $this->assertTrue($forStudent->data['by_staff']);
    }

    public function test_outsiders_and_guests_cannot_read_or_ask(): void
    {
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)->getJson(route('api.v1.lessons.comments', $this->lesson))->assertForbidden();
        $this->actingAs($outsider)->postJson(route('api.v1.lessons.comments.store', $this->lesson), ['body' => 'Hello there'])->assertForbidden();
        $this->actingAs(User::factory()->teacher()->create())->getJson(route('api.v1.lessons.comments', $this->lesson))->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->getJson(route('api.v1.lessons.comments', $this->lesson))->assertUnauthorized();
    }

    public function test_the_teacher_can_read_and_reply_but_not_ask(): void
    {
        $this->actingAs($this->teacher)
            ->getJson(route('api.v1.lessons.comments', $this->lesson))
            ->assertOk()
            ->assertJsonPath('meta.can_ask', false);

        $this->actingAs($this->teacher)
            ->postJson(route('api.v1.lessons.comments.store', $this->lesson), ['body' => 'A question'])
            ->assertForbidden();
    }

    public function test_a_teacher_reply_emails_the_asker_and_notifies_the_thread_in_app(): void
    {
        $question = $this->question();
        LessonComment::factory()->replyTo($question)->create(['user_id' => $this->classmate->id]);
        Notification::fake();

        $this->actingAs($this->teacher)
            ->postJson(route('api.v1.comments.reply', $question), ['body' => 'Because the resistance is fixed.'])
            ->assertCreated()
            ->assertJsonPath('data.by_staff', true)
            ->assertJsonPath('data.parent_id', $question->id);

        Notification::assertSentTo($this->asker, LessonQuestionReplied::class, fn (LessonQuestionReplied $n): bool => $n->via($this->asker) === ['database', 'mail']);
        Notification::assertSentTo($this->classmate, LessonQuestionReplied::class, fn (LessonQuestionReplied $n): bool => $n->via($this->classmate) === ['database']);
        Notification::assertNotSentTo($this->teacher, LessonQuestionReplied::class);
    }

    public function test_a_classmate_reply_notifies_the_asker_in_app_only(): void
    {
        $question = $this->question();
        Notification::fake();

        $this->actingAs($this->classmate)
            ->postJson(route('api.v1.comments.reply', $question), ['body' => 'I think it is Ohm’s law.'])
            ->assertCreated()
            ->assertJsonPath('data.by_staff', false);

        Notification::assertSentTo($this->asker, LessonQuestionReplied::class, fn (LessonQuestionReplied $n): bool => $n->via($this->asker) === ['database']);
        Notification::assertNotSentTo($this->classmate, LessonQuestionReplied::class);
    }

    public function test_threads_are_one_level_deep(): void
    {
        $reply = LessonComment::factory()->replyTo($this->question())->create(['user_id' => $this->classmate->id]);

        $this->actingAs($this->asker)
            ->postJson(route('api.v1.comments.reply', $reply), ['body' => 'Reply to a reply'])
            ->assertNotFound();
    }

    public function test_the_list_shows_questions_with_replies_and_hides_deleted_questions_without_replies(): void
    {
        $answered = $this->question();
        LessonComment::factory()->replyTo($answered)->byStaff($this->teacher)->create();
        $deletedWithReplies = $this->question();
        LessonComment::factory()->replyTo($deletedWithReplies)->create(['user_id' => $this->classmate->id]);
        $deletedWithReplies->delete();
        $this->question()->delete();

        $response = $this->actingAs($this->classmate)
            ->getJson(route('api.v1.lessons.comments', $this->lesson))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.can_ask', true);

        $deleted = collect($response->json('data'))->firstWhere('id', $deletedWithReplies->id);
        $this->assertTrue($deleted['is_deleted']);
        $this->assertNull($deleted['body']);
        $this->assertNull($deleted['author']);
        $this->assertCount(1, $deleted['replies']);

        $first = collect($response->json('data'))->firstWhere('id', $answered->id);
        $this->assertTrue($first['replies'][0]['by_staff']);
        $this->assertFalse($first['can']['resolve']);
    }

    public function test_the_unresolved_and_mine_filters(): void
    {
        $open = $this->question();
        $this->question()->update(['resolved_at' => now()]);
        LessonComment::factory()->create(['lesson_id' => $this->lesson->id, 'user_id' => $this->classmate->id]);

        $this->actingAs($this->asker)->getJson(route('api.v1.lessons.comments', [$this->lesson, 'filter' => 'unresolved']))
            ->assertJsonCount(2, 'data');
        $this->actingAs($this->asker)->getJson(route('api.v1.lessons.comments', [$this->lesson, 'filter' => 'mine']))
            ->assertJsonCount(2, 'data');
        $this->actingAs($this->asker)->getJson(route('api.v1.lessons.comments', [$this->lesson, 'filter' => 'mine']))
            ->assertJsonFragment(['id' => $open->id]);
    }

    public function test_authors_edit_within_the_window_only(): void
    {
        $question = $this->question();

        $this->actingAs($this->asker)
            ->patchJson(route('api.v1.comments.update', $question), ['body' => 'Fixed typo'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Fixed typo');
        $this->assertNotNull($question->fresh()?->edited_at);

        $this->actingAs($this->classmate)
            ->patchJson(route('api.v1.comments.update', $question), ['body' => 'Not mine'])
            ->assertForbidden();

        $this->travel(16)->minutes();
        $this->actingAs($this->asker)
            ->patchJson(route('api.v1.comments.update', $question), ['body' => 'Too late'])
            ->assertForbidden()
            ->assertJsonPath('message', __('qa.errors.edit_window', ['minutes' => 15]));
    }

    public function test_authors_delete_their_own_posts_and_the_teacher_deletes_any(): void
    {
        $question = $this->question();
        $reply = LessonComment::factory()->replyTo($question)->create(['user_id' => $this->classmate->id]);

        $this->actingAs($this->asker)->deleteJson(route('api.v1.comments.destroy', $reply))->assertForbidden();
        $this->actingAs($this->teacher)->deleteJson(route('api.v1.comments.destroy', $reply))->assertNoContent();
        $this->actingAs($this->asker)->deleteJson(route('api.v1.comments.destroy', $question))->assertNoContent();

        $this->assertSoftDeleted($reply);
        $this->assertSoftDeleted($question);
    }

    public function test_the_asker_resolves_and_reopens_but_a_classmate_cannot(): void
    {
        $question = $this->question();

        $this->actingAs($this->classmate)->postJson(route('api.v1.comments.resolve', $question))->assertForbidden();

        $this->actingAs($this->asker)->postJson(route('api.v1.comments.resolve', $question))
            ->assertOk()
            ->assertJsonPath('data.resolved_at', fn (?string $at) => $at !== null);

        $this->actingAs($this->teacher)->postJson(route('api.v1.comments.reopen', $question))
            ->assertOk()
            ->assertJsonPath('data.resolved_at', null);
    }

    public function test_the_teacher_accepting_an_answer_emails_the_asker_and_tells_the_author(): void
    {
        $question = $this->question();
        $reply = LessonComment::factory()->replyTo($question)->create(['user_id' => $this->classmate->id]);
        Notification::fake();

        $this->actingAs($this->teacher)
            ->postJson(route('api.v1.comments.accept', $reply))
            ->assertOk()
            ->assertJsonPath('data.id', $question->id)
            ->assertJsonPath('data.accepted_reply_id', $reply->id)
            ->assertJsonPath('data.resolved_at', fn (?string $at) => $at !== null);

        Notification::assertSentTo($this->asker, LessonAnswerAccepted::class, fn (LessonAnswerAccepted $n): bool => $n->via($this->asker) === ['database', 'mail']);
        Notification::assertSentTo($this->classmate, LessonAnswerAccepted::class, fn (LessonAnswerAccepted $n): bool => $n->via($this->classmate) === ['database']);
    }

    public function test_the_asker_accepting_sends_no_email_to_themself(): void
    {
        $question = $this->question();
        $reply = LessonComment::factory()->replyTo($question)->byStaff($this->teacher)->create();
        Notification::fake();

        $this->actingAs($this->asker)->postJson(route('api.v1.comments.accept', $reply))->assertOk();

        Notification::assertNotSentTo($this->asker, LessonAnswerAccepted::class);
        Notification::assertSentTo($this->teacher, LessonAnswerAccepted::class);
    }

    public function test_deleting_the_accepted_reply_clears_it(): void
    {
        $question = $this->question();
        $reply = LessonComment::factory()->replyTo($question)->create(['user_id' => $this->classmate->id]);
        $question->update(['accepted_reply_id' => $reply->id, 'resolved_at' => now()]);

        $this->actingAs($this->classmate)->deleteJson(route('api.v1.comments.destroy', $reply))->assertNoContent();

        $this->assertNull($question->fresh()?->accepted_reply_id);
    }

    public function test_body_is_required_and_limited(): void
    {
        $this->actingAs($this->asker)
            ->postJson(route('api.v1.lessons.comments.store', $this->lesson), ['body' => str_repeat('a', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }
}
