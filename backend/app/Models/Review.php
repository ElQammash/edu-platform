<?php

namespace App\Models;

use App\Observers\ReviewObserver;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's rating (1–5) and comment on a course, one per course.
 *
 * @property int $id
 * @property int $course_id
 * @property int $student_id
 * @property int $rating
 * @property string|null $comment
 * @property string|null $teacher_reply
 * @property CarbonImmutable|null $teacher_replied_at
 * @property CarbonImmutable|null $hidden_at
 * @property int|null $hidden_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Course $course
 * @property-read User $student
 */
#[ObservedBy(ReviewObserver::class)]
#[Fillable(['course_id', 'student_id', 'rating', 'comment', 'teacher_reply', 'teacher_replied_at', 'hidden_at', 'hidden_by'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /** Students review a course once they have done this much of it. */
    public const MIN_PROGRESS = 30;

    public const MAX_LENGTH = 1000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'teacher_replied_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * Reviews shown on the course page and counted in its rating.
     *
     * @param  Builder<Review>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereNull('hidden_at');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
