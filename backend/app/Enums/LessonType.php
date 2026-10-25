<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LessonType: string implements HasLabel
{
    case Video = 'video';
    case Live = 'live';
    case Text = 'text';

    public function label(): string
    {
        return __('enums.lesson_type.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Types teachers can pick today; live sessions arrive in phase 5.
     *
     * @return array<string, string>
     */
    public static function availableOptions(): array
    {
        return [
            self::Video->value => self::Video->label(),
            self::Text->value => self::Text->label(),
        ];
    }
}
