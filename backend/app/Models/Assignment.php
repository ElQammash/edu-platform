<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $lesson_id
 * @property string $title
 * @property string|null $instructions
 * @property CarbonImmutable|null $due_at
 * @property int $max_score
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Lesson $lesson
 */
#[Fillable(['lesson_id', 'title', 'instructions', 'due_at', 'max_score'])]
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_score' => 'integer',
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
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function course(): Course
    {
        return $this->lesson->course();
    }

    public function isPastDue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }
}
