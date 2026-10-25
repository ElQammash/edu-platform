<?php

namespace Tests\Feature\Api\V1\Quizzes;

use App\Enums\ShowAnswers;
use App\Models\AttemptAnswer;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuizGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QuizApiTest extends TestCase
{
    use RefreshDatabase;

    private Quiz $quiz;

    private Question $mcq;

    private Question $tf;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quiz = Quiz::factory()->create(['time_limit_minutes' => 10, 'pass_score' => 60]);
        $this->mcq = Question::factory()->for($this->quiz)->create(['points' => 3, 'order' => 1]);
        $this->tf = Question::factory()->for($this->quiz)->trueFalse()->create(['points' => 2, 'order' => 2]);
        $this->student = $this->enrolledStudent();
    }

    private function enrolledStudent(): User
    {
        $student = User::factory()->student()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $this->quiz->course()->id]);

        return $student;
    }

    private function correct(Question $question): int
    {
        return (int) $question->options()->where('is_correct', true)->value('id');
    }

    private function start(?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->student)->postJson(route('api.v1.quizzes.start', $this->quiz));
    }

    /**
     * Take the quiz and answer everything correctly.
     */
    private function takeAndSubmit(): TestResponse
    {
        $id = $this->start()->json('data.id');

        $this->putJson(route('api.v1.attempts.answers', $id), ['answers' => [
            ['question_id' => $this->mcq->id, 'option_id' => $this->correct($this->mcq)],
            ['question_id' => $this->tf->id, 'option_id' => $this->correct($this->tf)],
        ]])->assertNoContent();

        return $this->postJson(route('api.v1.attempts.submit', $id));
    }

    private function assertHidesCorrectness(TestResponse $response): void
    {
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('is_correct', $body);
        $this->assertStringNotContainsString('points_awarded', $body);
    }

    public function test_the_lesson_shows_its_quiz_to_enrolled_students(): void
    {
        $this->actingAs($this->student)
            ->getJson(route('api.v1.lessons.show', $this->quiz->lesson))
            ->assertJsonPath('data.quiz.id', $this->quiz->id)
            ->assertJsonPath('data.quiz.questions_count', 2)
            ->assertJsonPath('data.quiz.total_points', 5);
    }

    public function test_quiz_page_says_whether_the_student_can_start(): void
    {
        $this->actingAs($this->student)
            ->getJson(route('api.v1.quizzes.show', $this->quiz))
            ->assertOk()
            ->assertJsonPath('data.can_start', true)
            ->assertJsonPath('data.attempts', [])
            ->assertJsonPath('data.open_attempt_id', null);
    }

    public function test_starting_returns_questions_without_any_correctness_data(): void
    {
        $response = $this->start()
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonCount(2, 'data.questions')
            ->assertJsonPath('data.questions.0.id', $this->mcq->id)
            ->assertJsonCount(4, 'data.questions.0.options')
            ->assertJsonCount(2, 'data.questions.1.options');

        $this->assertHidesCorrectness($response);
        $this->assertNotNull($response->json('data.expires_at'));
        $this->assertNotNull($response->json('data.server_time'));
    }

    public function test_starting_again_resumes_the_same_attempt_with_saved_answers(): void
    {
        $id = $this->start()->json('data.id');
        $this->putJson(route('api.v1.attempts.answers', $id), ['answers' => [
            ['question_id' => $this->mcq->id, 'option_id' => $this->correct($this->mcq)],
        ]])->assertNoContent();

        $response = $this->start()->assertOk()->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.questions.0.answer.option_id', $this->correct($this->mcq));

        $this->assertHidesCorrectness($response);
    }

    public function test_submitting_grades_and_reveals_answers_by_default(): void
    {
        $this->takeAndSubmit()
            ->assertOk()
            ->assertJsonPath('data.status', 'graded')
            ->assertJsonPath('data.score', 5)
            ->assertJsonPath('data.percent', 100)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.answers_revealed', true)
            ->assertJsonPath('data.questions.0.points_awarded', 3);
    }

    public function test_never_show_answers_gives_the_score_only(): void
    {
        $this->quiz->update(['show_answers' => ShowAnswers::Never]);

        $response = $this->takeAndSubmit()
            ->assertJsonPath('data.score', 5)
            ->assertJsonPath('data.answers_revealed', false);

        $this->assertHidesCorrectness($response);
    }

    public function test_answers_after_the_due_date_stay_hidden_until_it_passes(): void
    {
        $this->quiz->update(['due_at' => now()->addDay()]);

        $id = $this->takeAndSubmit()->assertJsonPath('data.answers_revealed', false)->json('data.id');

        $this->travel(2)->days();
        $this->actingAs($this->student)
            ->getJson(route('api.v1.attempts.show', $id))
            ->assertJsonPath('data.answers_revealed', true);
    }

    public function test_essays_keep_the_attempt_pending_with_nothing_revealed(): void
    {
        $essay = Question::factory()->for($this->quiz)->essay(5)->create(['order' => 3]);
        $id = $this->start()->json('data.id');
        $this->putJson(route('api.v1.attempts.answers', $id), ['answers' => [
            ['question_id' => $essay->id, 'text_answer' => 'Long answer'],
        ]]);

        $response = $this->postJson(route('api.v1.attempts.submit', $id))
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.score', null);
        $this->assertHidesCorrectness($response);

        $answer = AttemptAnswer::where('question_id', $essay->id)->sole();
        app(QuizGradingService::class)->gradeEssay($answer, $this->quiz->course()->teacher, 5);

        $this->actingAs($this->student)
            ->getJson(route('api.v1.attempts.show', $id))
            ->assertJsonPath('data.status', 'graded')
            ->assertJsonPath('data.score', 5);
    }

    public function test_the_attempt_limit_is_enforced(): void
    {
        $this->takeAndSubmit();

        $this->start()->assertForbidden()->assertJsonPath('message', __('quizzes.errors.no_attempts_left'));

        $this->quiz->update(['max_attempts' => 2]);
        $this->start()->assertCreated();
    }

    public function test_the_time_limit_is_enforced_by_the_server(): void
    {
        $id = $this->start()->json('data.id');

        $this->travel(11)->minutes();

        $this->putJson(route('api.v1.attempts.answers', $id), ['answers' => [
            ['question_id' => $this->mcq->id, 'option_id' => $this->correct($this->mcq)],
        ]])->assertForbidden();

        $this->postJson(route('api.v1.attempts.submit', $id))->assertForbidden();

        $this->actingAs($this->student)
            ->getJson(route('api.v1.attempts.show', $id))
            ->assertJsonPath('data.status', 'graded')
            ->assertJsonPath('data.score', 0);
    }

    public function test_answers_cannot_change_after_submitting(): void
    {
        $id = $this->takeAndSubmit()->json('data.id');

        $this->putJson(route('api.v1.attempts.answers', $id), ['answers' => [
            ['question_id' => $this->mcq->id, 'option_id' => null],
        ]])->assertForbidden();
    }

    public function test_a_closed_quiz_cannot_be_started(): void
    {
        $this->quiz->update(['due_at' => now()->subMinute()]);

        $this->start()->assertForbidden()->assertJsonPath('message', __('quizzes.errors.closed'));
    }

    public function test_students_who_are_not_enrolled_and_teachers_cannot_take_it(): void
    {
        $this->start(User::factory()->student()->create())->assertForbidden();
        $this->start($this->quiz->course()->teacher)->assertForbidden();
    }

    public function test_students_cannot_open_each_others_attempts(): void
    {
        $id = $this->start()->json('data.id');

        $this->actingAs($this->enrolledStudent())
            ->getJson(route('api.v1.attempts.show', $id))
            ->assertForbidden();
    }

    public function test_my_grades_lists_the_course_with_lesson_and_course_grades(): void
    {
        $this->takeAndSubmit();
        QuizAttempt::query()->update(['score' => 3]); // 60%

        $this->actingAs($this->student)
            ->getJson(route('api.v1.me.grades'))
            ->assertOk()
            ->assertJsonPath('data.0.course.id', $this->quiz->course()->id)
            ->assertJsonPath('data.0.percent', 60)
            ->assertJsonPath('data.0.done', 1)
            ->assertJsonPath('data.0.total', 1)
            ->assertJsonPath('data.0.lessons.0.quiz.status', 'graded')
            ->assertJsonPath('data.0.lessons.0.assignment', null);
    }
}
