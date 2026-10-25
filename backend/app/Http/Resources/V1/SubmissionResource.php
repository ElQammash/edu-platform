<?php

namespace App\Http\Resources\V1;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Submission
 */
class SubmissionResource extends JsonResource
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
            'content' => $this->content,
            // The file itself is only served by an authorized endpoint.
            'file' => $this->hasFile() ? [
                'name' => $this->file_name,
                'mime' => $this->file_mime,
                'size' => $this->file_size,
                'url' => route('api.v1.submissions.file', $this->resource),
            ] : null,
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'is_late' => $this->isLate(),
            'is_graded' => $this->isGraded(),
            'score' => $this->score !== null ? (float) $this->score : null,
            'feedback' => $this->feedback,
            'graded_at' => $this->graded_at?->toIso8601String(),
            'can_edit' => ! $this->isGraded(),
        ];
    }
}
