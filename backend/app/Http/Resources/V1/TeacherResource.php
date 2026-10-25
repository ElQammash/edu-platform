<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Public teacher page: the summary plus bio and published courses.
 *
 * @mixin User
 */
class TeacherResource extends TeacherSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'bio' => $this->teacherProfile?->bio,
            'courses_count' => $this->whenCounted('courses'),
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}
