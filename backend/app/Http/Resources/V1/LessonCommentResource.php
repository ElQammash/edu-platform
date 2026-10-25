<?php

namespace App\Http\Resources\V1;

use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A question with its replies, or a single reply. A deleted question that
 * still has replies keeps its place, without its text or author.
 *
 * @mixin LessonComment
 */
class LessonCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $deleted = $this->trashed();

        $data = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'body' => $deleted ? null : $this->body,
            'is_deleted' => $deleted,
            'by_staff' => $this->by_staff,
            'author' => $deleted ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->avatarUrl($this->user),
            ],
            'is_mine' => $user !== null && $this->user_id === $user->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'editable_until' => $this->isEditable() ? $this->created_at?->addMinutes(LessonComment::EDIT_MINUTES)->toIso8601String() : null,
            'can' => [
                'edit' => ! $deleted && (bool) $user?->can('update', $this->resource),
                'delete' => ! $deleted && (bool) $user?->can('delete', $this->resource),
                'resolve' => $this->isQuestion() && ! $deleted && (bool) $user?->can('resolve', $this->resource),
                'accept' => ! $this->isQuestion() && ! $deleted && (bool) $user?->can('accept', $this->resource),
            ],
        ];

        if (! $this->isQuestion()) {
            return $data;
        }

        return [
            ...$data,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'accepted_reply_id' => $this->accepted_reply_id,
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }

    private function avatarUrl(User $user): ?string
    {
        $avatar = $user->teacherProfile->avatar ?? $user->studentProfile->avatar ?? null;

        return $avatar ? Storage::disk('public')->url($avatar) : null;
    }
}
