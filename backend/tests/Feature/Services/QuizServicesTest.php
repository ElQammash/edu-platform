<?php

namespace Tests\Feature\Services;

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizGraded;
use App\Services\QuizAttemptService;
use App\Services\QuizGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizServicesTest extends TestCase
{
    use RefreshDatabase;

    private QuizAttemptService $attempts;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attempts = app(QuizAttemptService::class);
        $this->student = User::factory()->student()->create();
    }

    private function correctOption(Question $question): int
    {
        return (int) $question->options()->where('is_correct', true)->value('id');
    }

    private function wrongOption(Question $question): int
    {
        return (int) $question->options()->where('is_correct', false)->value('id');
    }

    public function test_starting_twice_resumes_the_open_attempt(): void
    {
        $quiz = Quiz::factory()->has(Question::factory()->count(2))->create();

        $first = $this->attempts->start($this->student, $quiz);
        $second = $this->attempts->start($this->student, $quiz);

        $this->assertSame($first->id, $second->id);
        $this->assertEquals(4, $first->max_score);
        $this->assertTrue($first->expires_at?->equalTo($first->started_at->addMinutes(15)));
    }

    public function test_the_deadline_never_goes_past_the_due_date(): void
    {
        $quiz = Quiz::factory()->has(Question::factory())->create(['time_limit_minutes' => 60, 'due_at' => now()->addMinutes(10)]);

        $attempt = $this->attempts->start($this->student, $quiz);

        $this->assertTrue($attempt->expires_at?->equalTo($quiz->due_at));
    }

    public function test_shuffled_order_is_stored_and_kept(): void
    {
        $quiz = Quiz::factory()->has(Question::factory()->count(8))->create(['shuffle' => true]);

        $attempt = $this->attempts->start($this->student, $quiz);

        $this->assertEqualsCanonicalizing($quiz->questions->pluck('id')->all(), $attempt->question_order);
        foreach ($quiz->questions as $question) {
            $this->assertEqualsCanonicalizing($question->options->pluck('id')->all(), $attempt->option_order[$question->id]);
        }
        $this->assertSame($attempt->question_order, $attempt->fresh()?->question_order);
    }

    public function test_auto_grading_scores_mcq_and_true_false_and_empty_answers(): void
    {
        $quiz = Quiz::factory()->create();
        $mcq = Question::factory()->for($quiz)->create(['points' => 3]);
        $tf = Question::factory()->for($quiz)->trueFalse()->create(['points' => 2]);
        $skipped = Question::factory()->for($quiz)->create(['points' => 5]);

        $attempt = $this->attempts->start($this->student, $quiz);
        $this->attempts->saveAnswers($attempt, [
            ['question_id' => $mcq->id, 'option_id' => $this->correctOption($mcq)],
            ['question_id' => $tf->id, 'option_id' => $this->wrongOption($tf)],
        ]);
        $attempt = $this->attempts->submit($attempt);

        $this->assertTrue($attempt->isGraded());
        $this->assertEquals(3, $attempt->score);
        $this->assertEquals(10, $attempt->max_score);
        $this->assertSame(30.0, $attempt->percent());
        $this->assertFalse($attempt->passed());
        $this->assertEquals(0, AttemptAnswer::where('question_id', $skipped->id)->value('points_awarded'));
    }

    public function test_essays_wait_for_the_teacher_and_grading_the_last_one_notifies(): void
    {
        Notification::fake();
        $quiz = Quiz::factory()->create();
        $mcq = Question::factory()->for($quiz)->create(['points' => 5]);
        $essay = Question::factory()->for($quiz)->essay(5)->create();

        $attempt = $this->attempts->start($this->student, $quiz);
        $this->attempts->saveAnswers($attempt, [
            ['question_id' => $mcq->id, 'option_id' => $this->correctOption($mcq)],
            ['question_id' => $essay->id, 'text_answer' => 'My essay'],
        ]);
        $attempt = $this->attempts->submit($attempt);

        $this->assertTrue($attempt->isSubmitted());
        $this->assertFalse($attempt->isGraded());
        $this->assertNull($attempt->percent());

        $answer = AttemptAnswer::where('question_id', $essay->id)->sole();
        app(QuizGradingService::class)->gradeEssay($answer, User::factory()->teacher()->create(), 4);

        $attempt->refresh();
        $this->assertTrue($attempt->isGraded());
        $this->assertEquals(9, $attempt->score);
        Notification::assertSentTo($this->student, QuizGraded::class);
    }

    public function test_essay_points_cannot_exceed_the_question(): void
    {
        $essay = Question::factory()->essay(5)->create();
        $attempt = $this->attempts->start($this->student, $essay->quiz);
        $this->attempts->saveAnswers($attempt, [['question_id' => $essay->id, 'text_answer' => 'x']]);
        $this->attempts->submit($attempt);

        $this->expectException(ValidationException::class);

        app(QuizGradingService::class)->gradeEssay(AttemptAnswer::sole(), User::factory()->teacher()->create(), 6);
    }

    public function test_answers_after_the_deadline_are_refused_and_the_attempt_is_closed_at_it(): void
    {
        $quiz = Quiz::factory()->has(Question::factory())->create(['time_limit_minutes' => 10]);
        $attempt = $this->attempts->start($this->student, $quiz);

        $this->travel(11)->minutes();

        try {
            $this->attempts->saveAnswers($attempt, [['question_id' => $quiz->questions->first()?->id ?? 0, 'option_id' => null]]);
            $this->fail('Answers after the deadline must be refused.');
        } catch (ValidationException) {
            // expected
        }

        $attempt->refresh();
        $this->assertTrue($attempt->isSubmitted());
        $this->assertTrue($attempt->submitted_at?->equalTo($attempt->expires_at));
        $this->assertEquals(0, $attempt->score);
    }

    public function test_options_must_belong_to_their_question(): void
    {
        $quiz = Quiz::factory()->create();
        [$a, $b] = Question::factory()->count(2)->for($quiz)->create()->all();
        $attempt = $this->attempts->start($this->student, $quiz);

        $this->expectException(ValidationException::class);

        $this->attempts->saveAnswers($attempt, [['question_id' => $a->id, 'option_id' => $this->correctOption($b)]]);
    }

    public function test_a_submitted_attempt_cannot_be_submitted_again(): void
    {
        $quiz = Quiz::factory()->has(Question::factory())->create();
        $attempt = $this->attempts->submit($this->attempts->start($this->student, $quiz));

        $this->expectException(ValidationException::class);

        $this->attempts->submit($attempt);
    }
}
