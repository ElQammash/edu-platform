<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sent as multipart/form-data (POST) so the avatar can be uploaded.
 * Teachers and admins edit their profile in the Filament panels.
 */
class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isStudent();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'parent_phone' => ['nullable', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
