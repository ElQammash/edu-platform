<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use App\Enums\Language;
use Carbon\CarbonImmutable;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $teacher_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $thumbnail
 * @property string $price
 * @property Language $language
 * @property CourseType $type
 * @property CourseStatus $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $teacher
 */
#[Fillable(['teacher_id', 'title', 'slug', 'description', 'thumbnail', 'price', 'language', 'type', 'status'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Course $course): void {
            if (blank($course->slug)) {
                $course->slug = static::uniqueSlug($course->title);
            }
        });

        // Remember when a course went live; the catalog sorts by it.
        static::saving(function (Course $course): void {
            if ($course->status === CourseStatus::Published && $course->published_at === null) {
                $course->published_at = now();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'language' => Language::class,
            'type' => CourseType::class,
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Slug from the title (Arabic is transliterated), made unique with a suffix.
     */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'course';
        $slug = $base;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.0;
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    /**
     * @param  Builder<Course>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', CourseStatus::Published);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order')->orderBy('id');
    }

    /**
     * @return HasManyThrough<Lesson, Section, $this>
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Section::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
            ->withPivot(['payment_status', 'enrolled_at', 'progress_percent'])
            ->withTimestamps();
    }
}
