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
     * Types offered in the lesson form.
     *
     * @return array<string, string>
     */
    public static function availableOptions(): array
    {
        return [
            self::Video->value => self::Video->label(),
            self::Live->value => self::Live->label(),
            self::Text->value => self::Text->label(),
        ];
    }
}
