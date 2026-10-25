<?php

namespace App\Http\Resources\V1;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAttempt
 */
class AttemptSummaryResource extends JsonResource
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
            // in_progress, pending (essays wait for the teacher) or graded.
            'status' => match (true) {
                $this->isGraded() => 'graded',
                $this->isSubmitted() => 'pending',
                default => 'in_progress',
            },
            'started_at' => $this->started_at->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'score' => $this->isGraded() ? (float) $this->score : null,
            'max_score' => (float) $this->max_score,
            'percent' => $this->percent(),
            'passed' => $this->isGraded() ? $this->passed() : null,
        ];
    }
}
