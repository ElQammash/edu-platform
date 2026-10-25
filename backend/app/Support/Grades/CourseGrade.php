<?php

namespace App\Support\Grades;

use App\Models\Course;

/**
 * A course's grade: the average of its lesson grades that count. Lessons
 * whose work is not due yet are left out rather than counted as 0; "done of
 * total" keeps those gaps visible.
 */
final readonly class CourseGrade
{
    public ?float $percent;

    public int $done;

    public int $total;

    /**
     * @param  list<LessonGrade>  $lessons  only lessons with an assignment or a quiz
     */
    public function __construct(
        public Course $course,
        public array $lessons,
    ) {
        $counted = array_values(array_filter(
            array_map(fn (LessonGrade $lesson) => $lesson->percent, $lessons),
            fn (?float $percent) => $percent !== null,
        ));

        $this->percent = $counted === [] ? null : round(array_sum($counted) / count($counted), 2);
        $this->total = count($lessons);
        $this->done = count(array_filter($lessons, fn (LessonGrade $lesson) => $lesson->isDone()));
    }
}
