<?php

namespace App\Http\Requests\Api\V1\Lessons;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sent by the video player every few seconds.
 */
class RecordProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'watched_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            // What the player reports; used when the teacher did not set a duration.
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ];
    }
}
