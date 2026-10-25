<?php

namespace App\Http\Resources\V1;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin StudentProfile
 */
class StudentProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'grade_level' => $this->grade_level,
            'parent_phone' => $this->parent_phone,
            'avatar_url' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
        ];
    }
}
