<?php

namespace App\Enums;

enum CourseType: string
{
    case Recorded = 'recorded';
    case Live = 'live';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return __('enums.course_type.'.$this->value);
    }
}
