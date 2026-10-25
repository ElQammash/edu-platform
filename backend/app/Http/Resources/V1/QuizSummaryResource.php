<?php

namespace App\Http\Resources\V1;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The quiz as shown in the lesson player.
 *
 * @mixin Quiz
 */
class QuizSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'time_limit_minutes' => $this->time_limit_minutes,
            'due_at' => $this->due_at?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'max_attempts' => $this->max_attempts,
            'pass_score' => $this->pass_score,
            'questions_count' => $this->questions()->count(),
            'total_points' => $this->totalPoints(),
        ];
    }
}
