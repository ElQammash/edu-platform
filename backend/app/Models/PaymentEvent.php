<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One gateway message about a payment, stored as received.
 *
 * @property int $id
 * @property int $payment_id
 * @property string $gateway_transaction_id
 * @property string $type
 * @property string|null $amount
 * @property string $source
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 * @property-read Payment $payment
 */
#[Fillable(['payment_id', 'gateway_transaction_id', 'type', 'amount', 'source', 'payload', 'created_at'])]
class PaymentEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_PENDING = 'pending';

    public const TYPE_FAILED = 'failed';

    public const TYPE_REFUND = 'refund';

    public const TYPE_VOID = 'void';

    /** A message we refused (amount or currency mismatch). */
    public const TYPE_REJECTED = 'rejected';

    public const SOURCE_CALLBACK = 'callback';

    public const SOURCE_REDIRECT = 'redirect';

    public const SOURCE_INQUIRY = 'inquiry';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
