<?php

namespace App\Payments;

use Illuminate\Support\Arr;

/**
 * One transaction as the gateway reports it, in Paymob's terms: the
 * payment itself, or a refund or void (a child transaction of the payment).
 */
final readonly class GatewayTransaction
{
    /**
     * @param  array<string, mixed>  $payload  as received, for the audit trail
     */
    public function __construct(
        public string $id,
        public ?string $orderId,
        public ?string $merchantOrderId,
        public int $amountCents,
        public string $currency,
        public bool $success,
        public bool $pending,
        public bool $isRefund,
        public bool $isVoid,
        public ?string $method,
        public array $payload,
    ) {}

    /**
     * From a Transaction Processed callback's "obj" (or an inquiry response).
     *
     * @param  array<string, mixed>  $obj
     */
    public static function fromPaymob(array $obj): self
    {
        $order = $obj['order'] ?? null;

        return new self(
            id: (string) Arr::get($obj, 'id'),
            orderId: is_array($order) ? self::stringOrNull($order['id'] ?? null) : self::stringOrNull($order),
            merchantOrderId: is_array($order) ? self::stringOrNull($order['merchant_order_id'] ?? null) : self::stringOrNull($obj['merchant_order_id'] ?? null),
            amountCents: (int) Arr::get($obj, 'amount_cents', 0),
            currency: (string) Arr::get($obj, 'currency', ''),
            success: self::bool($obj['success'] ?? false),
            pending: self::bool($obj['pending'] ?? false),
            isRefund: self::bool($obj['is_refund'] ?? false),
            isVoid: self::bool($obj['is_void'] ?? false),
            method: self::method(Arr::get($obj, 'source_data.type'), Arr::get($obj, 'source_data.pan')),
            payload: $obj,
        );
    }

    /**
     * From the browser redirect's query string (flat keys, string values).
     *
     * @param  array<string, string>  $query
     */
    public static function fromPaymobRedirect(array $query): self
    {
        return new self(
            id: (string) ($query['id'] ?? ''),
            orderId: self::stringOrNull($query['order'] ?? $query['order_id'] ?? null),
            merchantOrderId: self::stringOrNull($query['merchant_order_id'] ?? null),
            amountCents: (int) ($query['amount_cents'] ?? 0),
            currency: (string) ($query['currency'] ?? ''),
            success: self::bool($query['success'] ?? false),
            pending: self::bool($query['pending'] ?? false),
            isRefund: self::bool($query['is_refund'] ?? false),
            isVoid: self::bool($query['is_void'] ?? false),
            method: self::method($query['source_data.type'] ?? null, $query['source_data.pan'] ?? null),
            payload: $query,
        );
    }

    private static function bool(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * "card •••• 2346", "wallet", or null.
     */
    private static function method(mixed $type, mixed $pan): ?string
    {
        if (! is_string($type) || $type === '') {
            return null;
        }

        $last4 = is_string($pan) ? substr(preg_replace('/\D/', '', $pan) ?? '', -4) : '';

        return $last4 !== '' && $type === 'card' ? "card •••• {$last4}" : $type;
    }
}
