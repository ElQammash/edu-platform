<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CourseType: string implements HasLabel
{
    case Recorded = 'recorded';
    case Live = 'live';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return __('enums.course_type.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
