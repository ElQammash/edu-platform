<?php

namespace Tests\Feature\Services;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use App\Services\GradeCalculator;
use App\Support\Grades\ComponentGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->create();
        $this->section = Section::factory()->for($this->course)->create();
    }

    private function lesson(int $order): Lesson
    {
        return Lesson::factory()->for($this->section)->create(['order' => $order]);
    }

    private function gradedAttempt(Quiz $quiz, float $score, float $max): QuizAttempt
    {
        return QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => $score,
            'max_score' => $max,
        ]);
    }

    public function test_lesson_grade_is_the_average_of_assignment_and_quiz_percentages(): void
    {
        $lesson = $this->lesson(1);
        $assignment = Assignment::factory()->for($lesson)->create(['max_score' => 20]);
        Submission::factory()->graded(16)->create(['assignment_id' => $assignment->id, 'student_id' => $this->student->id]);
        $quiz = Quiz::factory()->for($lesson)->create();
        $this->gradedAttempt($quiz, 6, 10);

        $grade = app(GradeCalculator::class)->forCourse($this->student, $this->course);

        // (80 + 60) / 2: equal weight even though the max scores differ.
        $this->assertSame(70.0, $grade->lessons[0]->percent);
        $this->assertSame(70.0, $grade->percent);
        $this->assertSame(1, $grade->done);
        $this->assertSame(1, $grade->total);
    }

    public function test_a_lesson_with_only_one_component_uses_it(): void
    {
        $assignment = Assignment::factory()->for($this->lesson(1))->create(['max_score' => 10]);
        Submission::factory()->graded(9)->create(['assignment_id' => $assignment->id, 'student_id' => $this->student->id]);

        $this->assertSame(90.0, app(GradeCalculator::class)->forCourse($this->student, $this->course)->percent);
    }

    public function test_the_best_graded_attempt_counts(): void
    {
        $quiz = Quiz::factory()->for($this->lesson(1))->create(['max_attempts' => 3]);
        $this->gradedAttempt($quiz, 4, 10);
        $this->gradedAttempt($quiz, 9, 10);
        $this->gradedAttempt($quiz, 6, 10);

        $this->assertSame(90.0, app(GradeCalculator::class)->forCourse($this->student, $this->course)->percent);
    }

    public function test_a_quiz_with_an_attempt_still_being_graded_is_pending(): void
    {
        $quiz = Quiz::factory()->for($this->lesson(1))->create(['max_attempts' => 2]);
        $this->gradedAttempt($quiz, 0, 10);
        QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'student_id' => $this->student->id, 'submitted_at' => now()]);

        $grade = app(GradeCalculator::class)->forCourse($this->student, $this->course);

        // The second attempt may beat the first; no number until it is graded.
        $this->assertSame(ComponentGrade::PENDING, $grade->lessons[0]->quiz?->status);
        $this->assertNull($grade->percent);
    }

    public function test_missing_work_past_its_due_date_counts_as_zero(): void
    {
        $done = $this->lesson(1);
        $assignment = Assignment::factory()->for($done)->create(['max_score' => 10]);
        Submission::factory()->graded(10)->create(['assignment_id' => $assignment->id, 'student_id' => $this->student->id]);
        Quiz::factory()->for($this->lesson(2))->pastDue()->create();

        $grade = app(GradeCalculator::class)->forCourse($this->student, $this->course);

        $this->assertSame(ComponentGrade::MISSING, $grade->lessons[1]->quiz?->status);
        $this->assertSame(0.0, $grade->lessons[1]->percent);
        $this->assertSame(50.0, $grade->percent);
        $this->assertSame(1, $grade->done);
        $this->assertSame(2, $grade->total);
    }

    public function test_work_not_due_yet_or_without_a_due_date_is_left_out(): void
    {
        $assignment = Assignment::factory()->for($this->lesson(1))->create(['max_score' => 10]);
        Submission::factory()->graded(8)->create(['assignment_id' => $assignment->id, 'student_id' => $this->student->id]);
        Quiz::factory()->for($this->lesson(2))->dueIn(3)->create();
        Assignment::factory()->for($this->lesson(3))->withoutDeadline()->create();

        $grade = app(GradeCalculator::class)->forCourse($this->student, $this->course);

        $this->assertSame(80.0, $grade->percent);
        $this->assertNull($grade->lessons[1]->percent);
        $this->assertNull($grade->lessons[2]->percent);
        $this->assertSame(1, $grade->done);
        $this->assertSame(3, $grade->total);
    }

    public function test_submitted_but_ungraded_work_is_pending_and_not_counted(): void
    {
        $lesson = $this->lesson(1);
        $assignment = Assignment::factory()->for($lesson)->pastDue()->create();
        Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $this->student->id]);

        $grade = app(GradeCalculator::class)->forCourse($this->student, $this->course);

        $this->assertSame(ComponentGrade::PENDING, $grade->lessons[0]->assignment?->status);
        $this->assertNull($grade->percent);
        $this->assertSame(1, $grade->done);
    }

    public function test_lessons_without_assessments_are_ignored(): void
    {
        $this->lesson(1);
        Quiz::factory()->for($this->lesson(2))->has(Question::factory())->create();

        $grade = app(GradeCalculator::class)->forCourse($this->student, $this->course);

        $this->assertSame(1, $grade->total);
    }
}
