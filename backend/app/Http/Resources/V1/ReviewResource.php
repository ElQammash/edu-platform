<?php

namespace App\Http\Resources\V1;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A course review as shown on the course page. Only the student's first
 * name and avatar are public.
 *
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = $this->student->studentProfile?->avatar;

        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'student' => [
                'name' => (string) str($this->student->name)->before(' '),
                'avatar_url' => $avatar ? Storage::disk('public')->url($avatar) : null,
            ],
            'teacher_reply' => $this->teacher_reply,
            'teacher_replied_at' => $this->teacher_replied_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'is_mine' => $request->user()?->id === $this->student_id,
            // Only the author sees their own hidden review.
            'is_hidden' => $this->isHidden(),
        ];
    }
}
