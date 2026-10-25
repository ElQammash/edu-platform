<?php

namespace App\Http\Resources\V1;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The quiz's start page: rules, the student's attempts and whether they can
 * start (or resume) one now.
 *
 * @mixin Quiz
 */
class QuizResource extends QuizSummaryResource
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
        $user = $request->user();
        $take = $user ? Gate::forUser($user)->inspect('take', $this->resource) : null;

        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'lesson' => ['id' => $lesson->id, 'title' => $lesson->title],
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title],
            'attempts' => $this->whenLoaded('attempts', fn () => AttemptSummaryResource::collection($this->attempts)),
            'open_attempt_id' => $this->whenLoaded('attempts', fn () => $this->attempts->first(fn (QuizAttempt $a) => $a->isOpen())?->id),
            'can_start' => $take?->allowed() ?? false,
            'cannot_start_reason' => $take?->denied() ? $take->message() : null,
        ];
    }
}
