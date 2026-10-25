<?php

namespace App\Http\Resources\V1;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The assignment as shown in the lesson player: enough to link to it.
 *
 * @mixin Assignment
 */
class AssignmentSummaryResource extends JsonResource
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
            'due_at' => $this->due_at?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'max_score' => $this->max_score,
        ];
    }
}
