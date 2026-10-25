<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * When a student may see which answers were correct. Never before their
 * attempt is submitted and fully graded, whatever the setting.
 */
enum ShowAnswers: string implements HasLabel
{
    case AfterGrading = 'after_grading';
    case AfterDueDate = 'after_due_date';
    case Never = 'never';

    public function label(): string
    {
        return __('enums.show_answers.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
