<?php

namespace App\Http\Requests\Api\V1\Lessons;

use App\Models\LessonComment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A question, a reply or an edit of either.
 */
class PostCommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:2', 'max:'.LessonComment::MAX_LENGTH],
        ];
    }

    public function body(): string
    {
        return trim($this->string('body')->toString());
    }
}
