<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How an enrollment was paid for. Paymob (phase 7) adds online payments;
 * until then paid courses are enrolled manually by the admin.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    /** Free course, the student enrolled themselves. */
    case Free = 'free';

    /** Paid outside the platform (cash, Vodafone Cash...) and enrolled by the admin. */
    case Manual = 'manual';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Free => 'gray',
            self::Manual => 'success',
        };
    }
}
