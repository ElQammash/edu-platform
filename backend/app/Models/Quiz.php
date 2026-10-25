<?php

namespace App\Models;

use App\Enums\ShowAnswers;
use Carbon\CarbonImmutable;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $lesson_id
 * @property string $title
 * @property string|null $description
 * @property int|null $time_limit_minutes
 * @property int $pass_score
 * @property int $max_attempts
 * @property CarbonImmutable|null $due_at
 * @property ShowAnswers|null $show_answers
 * @property bool $shuffle
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Lesson $lesson
 */
#[Fillable(['lesson_id', 'title', 'description', 'time_limit_minutes', 'pass_score', 'max_attempts', 'due_at', 'show_answers', 'shuffle'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'time_limit_minutes' => 'integer',
            'pass_score' => 'integer',
            'max_attempts' => 'integer',
            'due_at' => 'datetime',
            'show_answers' => ShowAnswers::class,
            'shuffle' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order')->orderBy('id');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function course(): Course
    {
        return $this->lesson->course();
    }

    public function isPastDue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public function totalPoints(): int
    {
        return (int) $this->questions()->sum('points');
    }

    /**
     * The setting, or the default: after the due date when there is one,
     * otherwise right after grading.
     */
    public function effectiveShowAnswers(): ShowAnswers
    {
        return $this->show_answers ?? ($this->due_at ? ShowAnswers::AfterDueDate : ShowAnswers::AfterGrading);
    }

    /**
     * Questions cannot change once students have taken the quiz, so no
     * score is altered after the fact.
     */
    public function isLocked(): bool
    {
        return $this->attempts()->exists();
    }
}
