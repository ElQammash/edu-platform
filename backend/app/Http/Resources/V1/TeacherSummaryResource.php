<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * The public face of a teacher, as shown on course cards and pages.
 * Never includes the email address.
 *
 * @mixin User
 */
class TeacherSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->teacherProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialty' => $profile?->specialty,
            'avatar_url' => $profile?->avatar ? Storage::disk('public')->url($profile->avatar) : null,
            'rating' => (float) ($profile->rating ?? 0),
        ];
    }
}
