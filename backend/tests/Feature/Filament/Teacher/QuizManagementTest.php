<?php

namespace Tests\Feature\Filament\Teacher;

use App\Enums\QuestionType;
use App\Filament\Shared\Courses\LessonsRelationManager;
use App\Filament\Shared\Quizzes\QuestionsRelationManager;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages\EditSection;
use App\Filament\Teacher\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Filament\Teacher\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Teacher\Resources\Quizzes\Pages\ListQuizzes;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Notifications\QuizGraded;
use App\Services\QuizAttemptService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->actingAs($this->teacher);
        Filament::setCurrentPanel('teacher');

        $lesson = Lesson::factory()->for(Section::factory()->for(Course::factory()->for($this->teacher, 'teacher')))->create();
        $this->quiz = Quiz::factory()->for($lesson)->create();
    }

    private function questions(): Testable
    {
        return Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $this->quiz, 'pageClass' => EditQuiz::class]);
    }

    public function test_the_lesson_form_creates_a_quiz_when_a_title_is_given(): void
    {
        $section = $this->quiz->lesson->section;

        Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditSection::class])
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Waves',
                'type' => 'text',
                'content' => 'Read',
                'quiz' => ['title' => 'Waves quiz', 'pass_score' => 70, 'max_attempts' => 2, 'shuffle' => true],
            ])
            ->assertHasNoFormErrors();

        $quiz = Quiz::where('title', 'Waves quiz')->sole();
        $this->assertSame(70, $quiz->pass_score);
        $this->assertSame(2, $quiz->max_attempts);
        $this->assertTrue($quiz->shuffle);
    }

    public function test_teacher_sees_only_their_own_quizzes(): void
    {
        $theirs = Quiz::factory()->create();

        Livewire::test(ListQuizzes::class)
            ->assertCanSeeTableRecords([$this->quiz])
            ->assertCanNotSeeTableRecords([$theirs]);

        $this->get(route('filament.teacher.resources.quizzes.edit', ['record' => $theirs]))->assertNotFound();
    }

    public function test_a_multiple_choice_question_needs_exactly_one_correct_option(): void
    {
        // Plain keys and no default empty rows, so the filled options are the only ones.
        $undoRepeaterFake = Repeater::fake();

        $options = fn (array $correct) => collect(['A', 'B', 'C'])
            ->mapWithKeys(fn ($body, $i) => ['item'.$i => ['body' => $body, 'is_correct' => in_array($i, $correct, true)]])
            ->all();

        $this->questions()
            ->callAction(TestAction::make('create')->table(), ['type' => 'mcq', 'body' => 'Pick one', 'points' => 2, 'options' => $options([])])
            ->assertHasFormErrors(['options']);

        $this->questions()
            ->callAction(TestAction::make('create')->table(), ['type' => 'mcq', 'body' => 'Pick one', 'points' => 2, 'options' => $options([0, 2])])
            ->assertHasFormErrors(['options']);

        $this->questions()
            ->callAction(TestAction::make('create')->table(), ['type' => 'mcq', 'body' => 'Pick one', 'points' => 2, 'options' => $options([1])])
            ->assertHasNoFormErrors();

        $question = Question::sole();
        $this->assertSame(3, $question->options()->count());
        $this->assertSame('B', $question->options()->where('is_correct', true)->value('body'));

        $undoRepeaterFake();
    }

    public function test_a_true_false_question_gets_its_two_options(): void
    {
        $this->questions()
            ->callAction(TestAction::make('create')->table(), ['type' => 'true_false', 'body' => 'The sky is green', 'points' => 1, 'true_false_answer' => 'false'])
            ->assertHasNoFormErrors();

        $question = Question::sole();
        $this->assertSame(QuestionType::TrueFalse, $question->type);
        $this->assertSame([QuestionOption::TRUE, QuestionOption::FALSE], $question->options()->pluck('body')->all());
        $this->assertSame(QuestionOption::FALSE, $question->options()->where('is_correct', true)->value('body'));
    }

    public function test_an_essay_question_has_no_options(): void
    {
        $this->questions()
            ->callAction(TestAction::make('create')->table(), ['type' => 'essay', 'body' => 'Explain', 'points' => 5])
            ->assertHasNoFormErrors();

        $this->assertSame(0, QuestionOption::count());
    }

    public function test_questions_are_locked_once_students_have_taken_the_quiz(): void
    {
        Question::factory()->for($this->quiz)->create();
        app(QuizAttemptService::class)->start(User::factory()->student()->create(), $this->quiz);

        $this->questions()->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_teacher_grades_pending_essays_and_the_student_is_notified(): void
    {
        Notification::fake();
        $essay = Question::factory()->for($this->quiz)->essay(10)->create();
        $student = User::factory()->student()->create();
        $service = app(QuizAttemptService::class);
        $attempt = $service->start($student, $this->quiz);
        $service->saveAnswers($attempt, [['question_id' => $essay->id, 'text_answer' => 'An essay']]);
        $service->submit($attempt);

        $answer = AttemptAnswer::sole();

        Livewire::test(ListQuizAttempts::class)
            ->assertCanSeeTableRecords([$attempt])
            ->callAction(TestAction::make('gradeEssays')->table($attempt), ['points_'.$answer->id => 8])
            ->assertHasNoFormErrors();

        $attempt->refresh();
        $this->assertTrue($attempt->isGraded());
        $this->assertEquals(8, $attempt->score);
        Notification::assertSentTo($student, QuizGraded::class);
    }

    public function test_the_pending_queue_hides_other_teachers_attempts(): void
    {
        $other = Quiz::factory()->has(Question::factory()->essay())->create();
        $service = app(QuizAttemptService::class);
        $attempt = $service->start(User::factory()->student()->create(), $other);
        $service->saveAnswers($attempt, [['question_id' => $other->questions->first()?->id ?? 0, 'text_answer' => 'x']]);
        $service->submit($attempt);

        Livewire::test(ListQuizAttempts::class)->assertCanNotSeeTableRecords([$attempt]);
    }
}
