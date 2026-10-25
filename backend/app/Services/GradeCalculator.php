<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use App\Models\User;
use App\Support\Grades\ComponentGrade;
use App\Support\Grades\CourseGrade;
use App\Support\Grades\LessonGrade;
use Illuminate\Support\Collection;

/**
 * Lesson and course grades, computed from submissions and quiz attempts
 * (never stored). Rules:
 * - assignment: the graded submission's percentage;
 * - quiz: the best fully graded attempt's percentage;
 * - submitted but not graded yet: pending, not counted;
 * - not submitted after the due date: 0; not due yet or no due date: not counted;
 * - lesson grade: equal-weighted average of what counts;
 * - course grade: average of the lesson grades that count.
 */
class GradeCalculator
{
    public function __construct(private readonly QuizAttemptService $attempts) {}

    public function forCourse(User $student, Course $course): CourseGrade
    {
        /** @var Collection<int, Lesson> $lessons */
        $lessons = $course->lessons()
            ->with(['assignment', 'quiz', 'section'])
            ->orderBy('sections.order')
            ->orderBy('lessons.order')
            ->orderBy('lessons.id')
            ->get()
            ->filter(fn (Lesson $lesson) => $lesson->assignment !== null || $lesson->quiz !== null)
            ->values();

        $assignmentIds = $lessons->pluck('assignment.id')->filter();
        $quizIds = $lessons->pluck('quiz.id')->filter();

        $submissions = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->get()
            ->keyBy('assignment_id');

        $attempts = QuizAttempt::query()
            ->with('quiz')
            ->where('student_id', $student->id)
            ->whereIn('quiz_id', $quizIds)
            ->get()
            // An attempt left open past its deadline counts as submitted.
            ->map(fn (QuizAttempt $attempt) => $this->attempts->finalizeIfExpired($attempt))
            ->groupBy('quiz_id');

        $grades = $lessons->map(fn (Lesson $lesson) => new LessonGrade(
            $lesson,
            $lesson->assignment ? $this->assignmentGrade($lesson->assignment, $submissions->get($lesson->assignment->id)) : null,
            $lesson->quiz ? $this->quizGrade($lesson->quiz, $attempts->get($lesson->quiz->id, collect())) : null,
        ))->all();

        return new CourseGrade($course, array_values($grades));
    }

    public function assignmentGrade(Assignment $assignment, ?Submission $submission): ComponentGrade
    {
        if ($submission === null) {
            return new ComponentGrade($assignment->isPastDue() ? ComponentGrade::MISSING : ComponentGrade::OPEN, maxScore: $assignment->max_score);
        }

        if (! $submission->isGraded()) {
            return new ComponentGrade(ComponentGrade::PENDING, maxScore: $assignment->max_score);
        }

        return ComponentGrade::graded((float) $submission->score, $assignment->max_score);
    }

    /**
     * @param  Collection<int, QuizAttempt>  $attempts  the student's attempts at this quiz
     */
    public function quizGrade(Quiz $quiz, Collection $attempts): ComponentGrade
    {
        // The best attempt counts, so while any attempt still waits for essay
        // grading the grade is not final: show it as pending, not as a number
        // that may change.
        if ($attempts->contains(fn (QuizAttempt $attempt) => $attempt->isSubmitted() && ! $attempt->isGraded())) {
            return new ComponentGrade(ComponentGrade::PENDING);
        }

        $best = $attempts
            ->filter(fn (QuizAttempt $attempt) => $attempt->isGraded())
            ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->percent())
            ->first();

        if ($best) {
            return ComponentGrade::graded((float) $best->score, (float) $best->max_score);
        }

        return new ComponentGrade($quiz->isPastDue() ? ComponentGrade::MISSING : ComponentGrade::OPEN);
    }
}
