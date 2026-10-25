<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $student_id
 * @property string|null $content
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_mime
 * @property int|null $file_size
 * @property CarbonImmutable $submitted_at
 * @property string|null $score
 * @property string|null $feedback
 * @property int|null $graded_by
 * @property CarbonImmutable|null $graded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Assignment $assignment
 * @property-read User $student
 * @property-read User|null $grader
 */
#[Fillable(['assignment_id', 'student_id', 'content', 'file_path', 'file_name', 'file_mime', 'file_size', 'submitted_at'])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    /** Private disk for submission files (storage/app/private). */
    public const DISK = 'local';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    /**
     * Submitted (or last edited) after the deadline. Derived rather than
     * stored, so extending the deadline clears the flag.
     */
    public function isLate(): bool
    {
        $due = $this->assignment->due_at;

        return $due !== null && $this->submitted_at->greaterThan($due);
    }

    public function hasFile(): bool
    {
        return $this->file_path !== null;
    }

    /**
     * @param  Builder<Submission>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('graded_at');
    }

    /**
     * @return BelongsTo<Assignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
