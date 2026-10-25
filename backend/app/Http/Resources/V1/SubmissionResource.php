<?php

namespace App\Http\Resources\V1;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

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
            // A short-lived signed link: whoever received this response already
            // passed the policy check, and a plain link opened in a new tab has
            // no reliable way to carry the API session.
            'file' => $this->hasFile() ? [
                'name' => $this->file_name,
                'mime' => $this->file_mime,
                'size' => $this->file_size,
                'url' => URL::temporarySignedRoute('submissions.file.signed', now()->addMinutes(30), ['submission' => $this->id]),
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
