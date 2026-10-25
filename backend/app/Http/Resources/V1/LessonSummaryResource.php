<?php

namespace App\Http\Resources\V1;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A curriculum entry. Public, so it must never carry the lesson's content,
 * video link or meeting link: those are only in LessonResource, behind
 * LessonPolicy.
 *
 * @mixin Lesson
 */
class LessonSummaryResource extends JsonResource
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
            'type' => $this->type,
            'duration_seconds' => $this->duration_seconds,
            'is_free_preview' => $this->is_free_preview,
            'live_at' => $this->isLive() ? $this->live_at?->toIso8601String() : null,
            'order' => $this->order,
            // Only when the current student's progress was loaded.
            'is_completed' => $this->whenLoaded('progress', fn () => $this->progress->first()?->isCompleted() ?? false),
        ];
    }
}
