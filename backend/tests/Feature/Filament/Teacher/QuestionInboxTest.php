<?php

namespace Tests\Feature\Filament\Teacher;

use App\Filament\Teacher\Resources\Questions\Pages\ListQuestions;
use App\Filament\Teacher\Resources\Questions\QuestionResource;
use App\Filament\Teacher\Widgets\UnansweredQuestions;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Section;
use App\Models\User;
use App\Notifications\LessonAnswerAccepted;
use App\Notifications\LessonQuestionReplied;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Lesson $lesson;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($this->teacher, 'teacher')->create();
        $this->lesson = Lesson::factory()->for(Section::factory()->for($course))->create();
        $this->student = User::factory()->student()->create();
        Enrollment::factory()->create(['student_id' => $this->student->id, 'course_id' => $course->id]);

        $this->actingAs($this->teacher);
        Filament::setCurrentPanel('teacher');
    }

    private function question(?Lesson $lesson = null): LessonComment
    {
        return LessonComment::factory()->create([
            'lesson_id' => ($lesson ?? $this->lesson)->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_the_inbox_opens_on_unanswered_questions_of_own_courses(): void
    {
        $waiting = $this->question();
        $answered = $this->question();
        LessonComment::factory()->replyTo($answered)->byStaff($this->teacher)->create();
        $resolved = $this->question();
        $resolved->update(['resolved_at' => now()]);
        $theirs = $this->question(Lesson::factory()->create());

        Livewire::test(ListQuestions::class)
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$answered, $resolved, $theirs])
            ->filterTable('status', 'answered')
            ->assertCanSeeTableRecords([$answered])
            ->filterTable('status', 'resolved')
            ->assertCanSeeTableRecords([$resolved]);

        $this->assertSame('1', QuestionResource::getNavigationBadge());
    }

    public function test_a_classmate_reply_does_not_count_as_an_answer(): void
    {
        $question = $this->question();
        LessonComment::factory()->replyTo($question)->create();

        Livewire::test(ListQuestions::class)->assertCanSeeTableRecords([$question]);
    }

    public function test_the_teacher_replies_from_the_modal_and_the_student_is_notified(): void
    {
        Notification::fake();
        $question = $this->question();

        Livewire::test(ListQuestions::class)
            ->callAction(TestAction::make('thread')->table($question), ['body' => 'Check slide 4.', 'resolved' => false])
            ->assertHasNoFormErrors();

        $reply = $question->replies()->firstOrFail();
        $this->assertTrue($reply->by_staff);
        $this->assertSame('Check slide 4.', $reply->body);
        $this->assertFalse($question->fresh()?->isResolved());
        Notification::assertSentTo($this->student, LessonQuestionReplied::class);
    }

    public function test_accepting_an_answer_resolves_the_question(): void
    {
        $question = $this->question();
        $reply = LessonComment::factory()->replyTo($question)->create();
        Notification::fake();

        Livewire::test(ListQuestions::class)
            ->callAction(TestAction::make('thread')->table($question), ['accepted_reply_id' => $reply->id, 'resolved' => false])
            ->assertHasNoFormErrors();

        $question->refresh();
        $this->assertSame($reply->id, $question->accepted_reply_id);
        $this->assertTrue($question->isResolved());
        Notification::assertSentTo($this->student, LessonAnswerAccepted::class);
    }

    public function test_the_teacher_deletes_a_question(): void
    {
        $question = $this->question();

        Livewire::test(ListQuestions::class)
            ->callAction(TestAction::make('delete')->table($question));

        $this->assertSoftDeleted($question);
    }

    public function test_the_dashboard_widget_lists_unanswered_questions(): void
    {
        $waiting = $this->question();
        $resolved = $this->question();
        $resolved->update(['resolved_at' => now()]);

        Livewire::test(UnansweredQuestions::class)
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$resolved]);
    }
}
