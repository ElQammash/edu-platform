<?php

namespace App\Models;

use App\Enums\LessonType;
use App\Observers\LessonObserver;
use Carbon\CarbonImmutable;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property LessonType $type
 * @property string|null $content
 * @property string|null $video_url
 * @property int|null $duration_seconds
 * @property CarbonImmutable|null $live_at
 * @property string|null $meeting_url
 * @property string|null $recording_url
 * @property int $order
 * @property bool $is_free_preview
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Section $section
 */
#[Fillable(['section_id', 'title', 'type', 'content', 'video_url', 'duration_seconds', 'live_at', 'meeting_url', 'recording_url', 'order', 'is_free_preview'])]
#[ObservedBy(LessonObserver::class)]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /** The meeting link opens this long before a live session starts. */
    public const JOIN_OPENS_MINUTES_BEFORE = 15;

    /** Used when a live lesson has no duration set. */
    public const DEFAULT_LIVE_MINUTES = 60;

    public const LIVE_UPCOMING = 'upcoming';

    public const LIVE_NOW = 'live';

    public const LIVE_ENDED = 'ended';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'duration_seconds' => 'integer',
            'live_at' => 'datetime',
            'order' => 'integer',
            'is_free_preview' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return HasMany<LessonProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * @return HasOne<Assignment, $this>
     */
    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    /**
     * @return HasOne<Quiz, $this>
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Questions and replies (see LessonComment::isQuestion).
     *
     * @return HasMany<LessonComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    public function isLive(): bool
    {
        return $this->type === LessonType::Live && $this->live_at !== null;
    }

    public function liveEndsAt(): ?CarbonImmutable
    {
        return $this->isLive()
            ? $this->live_at?->addSeconds($this->duration_seconds ?: self::DEFAULT_LIVE_MINUTES * 60)
            : null;
    }

    public function joinOpensAt(): ?CarbonImmutable
    {
        return $this->isLive() ? $this->live_at?->subMinutes(self::JOIN_OPENS_MINUTES_BEFORE) : null;
    }

    /**
     * upcoming / live / ended, or null when this is not a scheduled live lesson.
     */
    public function liveState(): ?string
    {
        if (! $this->isLive()) {
            return null;
        }

        return match (true) {
            now()->greaterThanOrEqualTo($this->liveEndsAt()) => self::LIVE_ENDED,
            now()->greaterThanOrEqualTo($this->live_at) => self::LIVE_NOW,
            default => self::LIVE_UPCOMING,
        };
    }

    /**
     * The meeting link is available from 15 minutes before the start until the end.
     */
    public function canJoinNow(): bool
    {
        return $this->isLive()
            && now()->greaterThanOrEqualTo($this->joinOpensAt())
            && now()->lessThan($this->liveEndsAt());
    }

    public function course(): Course
    {
        return $this->section->course;
    }
}
