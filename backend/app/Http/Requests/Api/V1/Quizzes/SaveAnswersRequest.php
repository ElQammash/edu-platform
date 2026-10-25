<?php

namespace App\Http\Requests\Api\V1\Quizzes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Autosave: { answers: [{ question_id, option_id?, text_answer? }] }.
 * Whether a question and option belong to the attempt is checked by
 * QuizAttemptService.
 */
class SaveAnswersRequest extends FormRequest
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
            'answers' => ['present', 'array', 'max:200'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.option_id' => ['nullable', 'integer'],
            'answers.*.text_answer' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
