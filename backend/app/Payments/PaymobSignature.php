<?php

namespace App\Payments;

use Illuminate\Support\Arr;

/**
 * Paymob's HMAC: the values of fixed fields, in a fixed order, joined with
 * no separator and signed with HMAC-SHA512 using the account's HMAC secret.
 * Booleans are "true"/"false"; a missing value counts as an empty string.
 *
 * @see https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac/hmac-transaction-callback
 */
final class PaymobSignature
{
    /** Transaction Processed (POST) callback, keys inside "obj". */
    private const PROCESSED_KEYS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction', 'id',
        'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'is_voided', 'order.id', 'owner', 'pending', 'source_data.pan', 'source_data.sub_type',
        'source_data.type', 'success',
    ];

    /** Transaction Response (GET) redirect, flat query keys. */
    private const RESPONSE_KEYS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction', 'id',
        'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'is_voided', 'order', 'owner', 'pending', 'source_data.pan', 'source_data.sub_type',
        'source_data.type', 'success',
    ];

    public function __construct(private readonly string $secret) {}

    /**
     * @param  array<string, mixed>  $obj
     */
    public function signProcessed(array $obj): string
    {
        $values = array_map(fn (string $key): string => self::value(Arr::get($obj, $key)), self::PROCESSED_KEYS);

        return hash_hmac('sha512', implode('', $values), $this->secret);
    }

    /**
     * @param  array<string, string>  $query  keys exactly as sent (with dots, see PaymobRedirectController)
     */
    public function signResponse(array $query): string
    {
        $values = array_map(
            // Paymob's docs name the order field "order_id" for redirects; it is sent as "order".
            fn (string $key): string => self::value($query[$key] ?? ($key === 'order' ? ($query['order_id'] ?? null) : null)),
            self::RESPONSE_KEYS,
        );

        return hash_hmac('sha512', implode('', $values), $this->secret);
    }

    /**
     * @param  array<string, mixed>  $obj
     */
    public function verifyProcessed(array $obj, ?string $hmac): bool
    {
        return is_string($hmac) && $hmac !== '' && hash_equals($this->signProcessed($obj), strtolower($hmac));
    }

    /**
     * @param  array<string, string>  $query
     */
    public function verifyResponse(array $query, ?string $hmac): bool
    {
        return is_string($hmac) && $hmac !== '' && hash_equals($this->signResponse($query), strtolower($hmac));
    }

    private static function value(mixed $value): string
    {
        return match (true) {
            $value === true => 'true',
            $value === false => 'false',
            is_scalar($value) => (string) $value,
            default => '',
        };
    }
}
