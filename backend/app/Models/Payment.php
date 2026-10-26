<?php

namespace App\Models;

use App\Enums\PaymentState;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * An online payment for one course. Only a verified gateway message
 * changes its status (see PaymentService).
 *
 * @property int $id
 * @property string $reference
 * @property int $student_id
 * @property int $course_id
 * @property string $amount
 * @property string $refunded_amount
 * @property string $currency
 * @property string $gateway
 * @property PaymentState $status
 * @property string|null $gateway_order_id
 * @property string|null $gateway_transaction_id
 * @property string|null $payment_method
 * @property string|null $checkout_url
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $failed_at
 * @property CarbonImmutable|null $refunded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $student
 * @property-read Course $course
 * @property-read Enrollment|null $enrollment
 */
#[Fillable([
    'reference', 'student_id', 'course_id', 'amount', 'refunded_amount', 'currency', 'gateway', 'status',
    'gateway_order_id', 'gateway_transaction_id', 'payment_method', 'checkout_url',
    'expires_at', 'paid_at', 'failed_at', 'refunded_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /** How long a checkout stays open (also sent to the gateway). */
    public const VALID_MINUTES = 60;

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->reference ??= (string) Str::uuid();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'status' => PaymentState::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * The amount in the smallest unit (piasters), as gateways expect it.
     */
    public function amountCents(): int
    {
        return (int) round((float) $this->amount * 100);
    }

    public function netAmount(): float
    {
        return round((float) $this->amount - (float) $this->refunded_amount, 2);
    }

    /**
     * A pending checkout the student can still go back to.
     */
    public function isReusable(): bool
    {
        return $this->status === PaymentState::Pending
            && filled($this->checkout_url)
            && $this->expires_at !== null
            // Leave a margin, so the student doesn't land on a checkout about to close.
            && $this->expires_at->subMinutes(10)->isFuture();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<PaymentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderBy('id');
    }

    /**
     * @return HasOne<Enrollment, $this>
     */
    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class);
    }
}
