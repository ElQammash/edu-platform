<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The language a course is taught in (not the UI language).
 */
enum Language: string implements HasLabel
{
    case Arabic = 'ar';
    case English = 'en';

    public function label(): string
    {
        return __('enums.language.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
