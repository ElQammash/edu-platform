<?php

namespace App\Http\Resources\V1;

use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LessonProgress
 */
class LessonProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'lesson_id' => $this->lesson_id,
            'watched_seconds' => $this->watched_seconds,
            'is_completed' => $this->isCompleted(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
