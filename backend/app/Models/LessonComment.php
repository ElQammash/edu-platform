<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LessonCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A question on a lesson (parent_id null) or a reply to one. Threads are
 * one level deep: replies always point at the question.
 *
 * @property int $id
 * @property int $lesson_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $body
 * @property bool $by_staff
 * @property CarbonImmutable|null $resolved_at
 * @property int|null $accepted_reply_id
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Lesson $lesson
 * @property-read User $user
 * @property-read LessonComment|null $parent
 * @property-read LessonComment|null $acceptedReply
 */
#[Fillable(['lesson_id', 'user_id', 'parent_id', 'body', 'by_staff', 'resolved_at', 'accepted_reply_id', 'edited_at'])]
class LessonComment extends Model
{
    /** @use HasFactory<LessonCommentFactory> */
    use HasFactory, SoftDeletes;

    /** Authors can fix a typo for this long after posting. */
    public const EDIT_MINUTES = 15;

    public const MAX_LENGTH = 2000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'by_staff' => 'boolean',
            'resolved_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    public function isQuestion(): bool
    {
        return $this->parent_id === null;
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->trashed()
            && $this->created_at !== null
            && $this->created_at->addMinutes(self::EDIT_MINUTES)->isFuture();
    }

    /**
     * The question of a thread (itself, for a question).
     */
    public function question(): LessonComment
    {
        return $this->isQuestion() ? $this : ($this->parent ?? $this);
    }

    /**
     * Questions still waiting for the teacher: open, and with no reply from
     * the course teacher or an admin.
     *
     * @param  Builder<LessonComment>  $query
     */
    #[Scope]
    protected function unanswered(Builder $query): void
    {
        $query->whereNull('parent_id')
            ->whereNull('resolved_at')
            ->whereDoesntHave('replies', fn (Builder $reply) => $reply->where('by_staff', true));
    }

    /**
     * @param  Builder<LessonComment>  $query
     */
    #[Scope]
    protected function forTeacher(Builder $query, int $teacherId): void
    {
        $query->whereHas('lesson.section.course', fn (Builder $q) => $q->where('teacher_id', $teacherId));
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<LessonComment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(LessonComment::class, 'parent_id');
    }

    /**
     * @return HasMany<LessonComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(LessonComment::class, 'parent_id')->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<LessonComment, $this>
     */
    public function acceptedReply(): BelongsTo
    {
        return $this->belongsTo(LessonComment::class, 'accepted_reply_id');
    }
}
