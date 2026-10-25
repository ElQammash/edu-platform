<?php

namespace App\Support\Grades;

/**
 * The state of one assessed piece of a lesson (its assignment or its quiz)
 * for one student.
 */
final readonly class ComponentGrade
{
    /** Graded: counts with its percentage. */
    public const GRADED = 'graded';

    /** Submitted, waiting for the teacher: not counted yet. */
    public const PENDING = 'pending';

    /** Not submitted and its due date has passed: counts as 0. */
    public const MISSING = 'missing';

    /** Not submitted, not due yet (or no due date): not counted. */
    public const OPEN = 'open';

    public function __construct(
        public string $status,
        public ?float $percent = null,
        public ?float $score = null,
        public ?float $maxScore = null,
    ) {}

    public static function graded(float $score, float $maxScore): self
    {
        $percent = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0.0;

        return new self(self::GRADED, $percent, $score, $maxScore);
    }

    /**
     * The percentage this component contributes to the lesson grade, or null
     * when it does not count (yet).
     */
    public function countedPercent(): ?float
    {
        return match ($this->status) {
            self::GRADED => $this->percent,
            self::MISSING => 0.0,
            default => null,
        };
    }

    /** Submitted (graded or waiting for grading). */
    public function isDone(): bool
    {
        return in_array($this->status, [self::GRADED, self::PENDING], true);
    }

    /**
     * @return array{status: string, percent: float|null, score: float|null, max_score: float|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'percent' => $this->status === self::MISSING ? 0.0 : $this->percent,
            'score' => $this->score,
            'max_score' => $this->maxScore,
        ];
    }
}
