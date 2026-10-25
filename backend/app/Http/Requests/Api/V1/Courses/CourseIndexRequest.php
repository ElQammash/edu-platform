<?php

namespace App\Http\Requests\Api\V1\Courses;

use App\Enums\CourseType;
use App\Enums\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Catalog filters: ?search=&language=ar&type=recorded&price=free&teacher=5&page=2
 */
class CourseIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', Rule::enum(Language::class)],
            'type' => ['nullable', Rule::enum(CourseType::class)],
            'price' => ['nullable', Rule::in(['free', 'paid'])],
            'teacher' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ];
    }
}
