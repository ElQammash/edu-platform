<?php

namespace App\Http\Resources\V1;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Enrollment
 */
class EnrollmentResource extends JsonResource
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
            'payment_status' => $this->payment_status,
            'enrolled_at' => $this->enrolled_at->toIso8601String(),
            'progress_percent' => $this->progress_percent,
            'course' => new CourseResource($this->whenLoaded('course')),
        ];
    }
}
