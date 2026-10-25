<?php

namespace App\Http\Requests\Api\V1\Assignments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * multipart/form-data: content (text), file (PDF or image), remove_file.
 */
class SubmitAssignmentRequest extends FormRequest
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
        /** @var list<string> $mimes */
        $mimes = config('academy.submissions.mimes');
        $maxKb = config('academy.submissions.max_file_kb');

        return [
            'content' => ['nullable', 'string', 'max:20000'],
            // `mimes` checks the file's real content type, not just its extension.
            'file' => ['nullable', 'file', 'mimes:'.implode(',', $mimes), 'max:'.$maxKb],
            'remove_file' => ['sometimes', 'boolean'],
        ];
    }
}
