<?php

namespace App\Http\Resources\V1;

use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin TeacherProfile
 */
class TeacherProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bio' => $this->bio,
            'specialty' => $this->specialty,
            'avatar_url' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'rating' => (float) $this->rating,
            'is_approved' => $this->is_approved,
        ];
    }
}
