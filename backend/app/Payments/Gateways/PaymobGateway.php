<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\CheckoutSession;
use App\Payments\GatewayTransaction;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Paymob Egypt: an Intention with the student's details opens Unified
 * Checkout (cards and mobile wallets). Card data never reaches our server.
 *
 * @see https://developers.paymob.com/paymob-docs/developers/intention-apis/create-intention
 */
class PaymobGateway implements PaymentGateway
{
    /**
     * @param  array<string, mixed>  $config  config('payments.paymob')
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return PaymentGatewayManager::PAYMOB;
    }

    /**
     * @throws RequestException when Paymob refuses the intention
     */
    public function createCheckout(Payment $payment): CheckoutSession
    {
        $student = $payment->student;
        [$first, $last] = array_pad(explode(' ', trim($student->name), 2), 2, '');

        $intention = $this->http()
            ->withHeader('Authorization', 'Token '.$this->config['secret_key'])
            ->post('/v1/intention/', [
                'amount' => $payment->amountCents(),
                'currency' => $payment->currency,
                'payment_methods' => $this->config['integration_ids'],
                'items' => [[
                    'name' => mb_substr($payment->course->title, 0, 50),
                    'amount' => $payment->amountCents(),
                    'description' => 'Course #'.$payment->course_id,
                    'quantity' => 1,
                ]],
                'billing_data' => [
                    'first_name' => $first !== '' ? $first : 'Student',
                    'last_name' => $last !== '' ? $last : 'NA',
                    'email' => $student->email,
                    'phone_number' => $student->studentProfile->parent_phone ?? 'NA',
                    'country' => 'EG',
                ],
                'special_reference' => $payment->reference,
                'notification_url' => route('api.v1.payments.paymob.callback'),
                'redirection_url' => route('payments.paymob.return'),
                'expiration' => Payment::VALID_MINUTES * 60,
            ])
            ->throw()
            ->json();

        $query = http_build_query([
            'publicKey' => $this->config['public_key'],
            'clientSecret' => $intention['client_secret'] ?? '',
        ]);

        return new CheckoutSession(
            url: rtrim((string) $this->config['checkout_url'], '/').'/?'.$query,
            gatewayOrderId: isset($intention['intention_order_id']) ? (string) $intention['intention_order_id'] : null,
        );
    }

    /**
     * The transaction of the payment's Paymob order, through the transaction
     * inquiry API (needs PAYMOB_API_KEY). Null when there is none yet.
     *
     * @throws RequestException
     */
    public function inquire(Payment $payment): ?GatewayTransaction
    {
        if (blank($this->config['api_key'] ?? null) || $payment->gateway_order_id === null) {
            return null;
        }

        $token = (string) $this->http()->post('/api/auth/tokens', ['api_key' => $this->config['api_key']])->throw()->json('token');

        $response = $this->http()
            ->withToken($token)
            ->post('/api/ecommerce/orders/transaction_inquiry', [
                'auth_token' => $token,
                'order_id' => $payment->gateway_order_id,
            ]);

        // No transaction for the order yet.
        if ($response->status() === 404) {
            return null;
        }

        $transaction = $response->throw()->json();

        return is_array($transaction) && isset($transaction['id']) ? GatewayTransaction::fromPaymob($transaction) : null;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) $this->config['base_url'])
            ->acceptJson()
            ->asJson()
            ->timeout((int) ($this->config['timeout'] ?? 15));
    }
}
