<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasLabel
{
    /** One correct option among several; auto-graded. */
    case Mcq = 'mcq';

    /** Two fixed options (true/false); auto-graded. */
    case TrueFalse = 'true_false';

    /** Free text; graded by the teacher. */
    case Essay = 'essay';

    public function label(): string
    {
        return __('enums.question_type.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function isAutoGraded(): bool
    {
        return $this !== self::Essay;
    }
}
