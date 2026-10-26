<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How an enrollment was paid for: free, outside the platform (enrolled by
 * the admin) or online through the payment gateway.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    /** Free course, the student enrolled themselves. */
    case Free = 'free';

    /** Paid outside the platform (cash, Vodafone Cash...) and enrolled by the admin. */
    case Manual = 'manual';

    /** Paid online (Paymob); the enrollment points at its payment. */
    case Paid = 'paid';

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
            self::Manual, self::Paid => 'success',
        };
    }
}
