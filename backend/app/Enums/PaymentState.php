<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where an online payment stands. (PaymentStatus is how an enrollment was
 * paid for: free, manual or online.)
 */
enum PaymentState: string implements HasColor, HasLabel
{
    /** Checkout started; waiting for the gateway. */
    case Pending = 'pending';

    case Paid = 'paid';

    case Failed = 'failed';

    /** Never completed within its validity. */
    case Expired = 'expired';

    /** Part of the amount went back; the student keeps the course. */
    case PartiallyRefunded = 'partially_refunded';

    /** All of it went back; the course access was removed. */
    case Refunded = 'refunded';

    public function label(): string
    {
        return __('enums.payment_state.'.$this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::PartiallyRefunded => 'info',
            self::Failed, self::Refunded => 'danger',
            self::Expired => 'gray',
        };
    }

    /**
     * Money was received (and not all of it given back).
     */
    public function grantsAccess(): bool
    {
        return $this === self::Paid || $this === self::PartiallyRefunded;
    }
}
