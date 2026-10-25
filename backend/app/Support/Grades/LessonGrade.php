<?php

namespace App\Support\Grades;

use App\Models\Lesson;

/**
 * A lesson's grade: the equal-weighted average of the percentages of its
 * assignment and quiz that count (graded, or missing past their due date as
 * 0). With only one of them, that one is the grade. Null while nothing counts.
 */
final readonly class LessonGrade
{
    public ?float $percent;

    public function __construct(
        public Lesson $lesson,
        public ?ComponentGrade $assignment,
        public ?ComponentGrade $quiz,
    ) {
        $counted = array_values(array_filter(
            [$assignment?->countedPercent(), $quiz?->countedPercent()],
            fn (?float $percent) => $percent !== null,
        ));

        $this->percent = $counted === [] ? null : round(array_sum($counted) / count($counted), 2);
    }

    /** Every assessed piece has been submitted. */
    public function isDone(): bool
    {
        foreach ([$this->assignment, $this->quiz] as $component) {
            if ($component !== null && ! $component->isDone()) {
                return false;
            }
        }

        return true;
    }
}
