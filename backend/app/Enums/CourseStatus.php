<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CourseStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return __('enums.course_status.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
        };
    }
}
