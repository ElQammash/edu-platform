<?php

namespace App\Models;

use App\Enums\ShowAnswers;
use Carbon\CarbonImmutable;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $quiz_id
 * @property int $student_id
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $graded_at
 * @property string|null $score
 * @property string $max_score
 * @property list<int> $question_order
 * @property array<int|string, list<int>> $option_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Quiz $quiz
 * @property-read User $student
 */
#[Fillable(['quiz_id', 'student_id', 'started_at', 'expires_at', 'max_score', 'question_order', 'option_order'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'question_order' => 'array',
            'option_order' => 'array',
        ];
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Still being answered: not submitted and time not up. */
    public function isOpen(): bool
    {
        return ! $this->isSubmitted() && ! $this->isExpired();
    }

    public function percent(): ?float
    {
        if (! $this->isGraded() || (float) $this->max_score <= 0) {
            return null;
        }

        return round((float) $this->score / (float) $this->max_score * 100, 2);
    }

    public function passed(): ?bool
    {
        $percent = $this->percent();

        return $percent === null ? null : $percent >= $this->quiz->pass_score;
    }

    /**
     * May the student see which answers were correct? Never before the attempt
     * is submitted and fully graded; after that, per the quiz setting.
     */
    public function revealsAnswers(): bool
    {
        if (! $this->isGraded()) {
            return false;
        }

        return match ($this->quiz->effectiveShowAnswers()) {
            ShowAnswers::AfterGrading => true,
            ShowAnswers::AfterDueDate => $this->quiz->isPastDue(),
            ShowAnswers::Never => false,
        };
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return HasMany<AttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }
}
