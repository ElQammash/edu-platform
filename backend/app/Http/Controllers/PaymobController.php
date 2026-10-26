<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Payments\GatewayTransaction;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Paymob's two ways back to us:
 *
 * - the Transaction Processed callback (server to server) is what counts:
 *   it is verified with the HMAC and applied;
 * - the browser redirect after checkout is verified the same way and
 *   applied too (harmless if the callback came first), then the student
 *   lands on the React result page.
 */
class PaymobController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentProcessor $processor,
    ) {}

    /**
     * POST /api/v1/payments/paymob/callback?hmac=...
     */
    public function callback(Request $request): JsonResponse
    {
        // The raw body: input() is trimmed and has empty strings turned to
        // null by middleware, which would change what the HMAC covers.
        $body = json_decode($request->getContent(), true);
        $obj = is_array($body) ? ($body['obj'] ?? null) : null;

        if (! is_array($obj)) {
            return response()->json(['message' => 'Unsupported callback.'], 400);
        }

        // Card token and other callback types carry no payment.
        if (strtoupper((string) ($body['type'] ?? 'TRANSACTION')) !== 'TRANSACTION') {
            return response()->json(['received' => true]);
        }

        $hmac = $request->query('hmac') ?? ($body['hmac'] ?? null);

        if (! $this->gateways->signature()->verifyProcessed($obj, is_string($hmac) ? $hmac : null)) {
            Log::warning('Paymob callback rejected: invalid HMAC', ['transaction' => $obj['id'] ?? null, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $this->processor->apply(GatewayTransaction::fromPaymob($obj), PaymentEvent::SOURCE_CALLBACK);

        return response()->json(['received' => true]);
    }

    /**
     * GET /payments/paymob/return?...&hmac=...: the student's browser after
     * checkout.
     */
    public function return(Request $request): RedirectResponse
    {
        $queryString = $request->server('QUERY_STRING');
        $query = self::rawQuery(is_string($queryString) ? $queryString : '');
        $transaction = GatewayTransaction::fromPaymobRedirect($query);

        $payment = $transaction->merchantOrderId !== null
            ? Payment::query()->where('reference', $transaction->merchantOrderId)->first()
            : ($transaction->orderId !== null ? Payment::query()->where('gateway_order_id', $transaction->orderId)->first() : null);

        if ($this->gateways->signature()->verifyResponse($query, $query['hmac'] ?? null)) {
            $payment = $this->processor->apply($transaction, PaymentEvent::SOURCE_REDIRECT) ?? $payment;
        } elseif ($query !== []) {
            // Show the page anyway: its status comes from our records, not this URL.
            Log::warning('Paymob redirect with an invalid HMAC', ['transaction' => $query['id'] ?? null]);
        }

        $frontend = rtrim((string) config('app.frontend_url'), '/');

        return redirect()->away($payment ? $frontend.'/payments/'.$payment->reference : $frontend.'/my-payments');
    }

    /**
     * The query string with its keys as sent. PHP's $_GET turns the dots in
     * "source_data.pan" into underscores, which would break the HMAC.
     *
     * @return array<string, string>
     */
    public static function rawQuery(string $queryString): array
    {
        $query = [];

        foreach (explode('&', $queryString) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $query[urldecode($key)] = urldecode($value);
        }

        return $query;
    }
}
