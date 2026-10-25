<?php

namespace App\Http\Resources\V1;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

/**
 * Full assignment with the signed-in student's submission.
 *
 * @mixin Assignment
 */
class AssignmentResource extends AssignmentSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lesson = $this->lesson;
        $course = $lesson->course();

        return [
            ...parent::toArray($request),
            'instructions' => $this->instructions,
            'lesson' => ['id' => $lesson->id, 'title' => $lesson->title],
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title],
            // Only for students: null before they submit.
            'submission' => $this->whenLoaded('submissions', function () {
                $submission = $this->submissions->first();

                return $submission instanceof Submission ? new SubmissionResource($submission) : null;
            }),
        ];
    }
}
