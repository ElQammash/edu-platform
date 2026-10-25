<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $question_id
 * @property string $body
 * @property bool $is_correct
 * @property int $order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Question $question
 */
#[Fillable(['question_id', 'body', 'is_correct', 'order'])]
// Hidden from any accidental serialization; resources reveal it explicitly.
#[Hidden(['is_correct'])]
class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasFactory;

    /**
     * Stored bodies of the two true/false options (order 0 and 1). Apps show
     * them translated, so a quiz reads right in either language.
     */
    public const TRUE = 'True';

    public const FALSE = 'False';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
