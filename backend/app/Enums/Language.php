<?php

namespace App\Enums;

/**
 * The language a course is taught in (not the UI language).
 */
enum Language: string
{
    case Arabic = 'ar';
    case English = 'en';

    public function label(): string
    {
        return __('enums.language.'.$this->value);
    }
}
