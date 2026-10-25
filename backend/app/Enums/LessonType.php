<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Live = 'live';
    case Text = 'text';

    public function label(): string
    {
        return __('enums.lesson_type.'.$this->value);
    }
}
