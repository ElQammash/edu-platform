<?php

namespace App\Http\Resources\V1;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => Str::limit(strip_tags((string) $this->description), 160),
            'description' => $this->when($this->relationLoaded('sections'), $this->description),
            'thumbnail_url' => $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null,
            'price' => (float) $this->price,
            'is_free' => $this->isFree(),
            'language' => $this->language,
            'type' => $this->type,
            'published_at' => $this->published_at?->toIso8601String(),
            'teacher' => new TeacherSummaryResource($this->whenLoaded('teacher')),
            'lessons_count' => $this->whenCounted('lessons'),
            'students_count' => $this->whenCounted('enrollments'),
            'total_duration_seconds' => $this->whenAggregated('lessons', 'duration_seconds', 'sum', fn ($sum) => (int) $sum),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            // The current user's enrollment, when the controller loaded it.
            'enrollment' => $this->whenLoaded('enrollments', function () {
                $enrollment = $this->enrollments->first();

                return $enrollment instanceof Enrollment ? new EnrollmentResource($enrollment) : null;
            }),
        ];
    }
}
