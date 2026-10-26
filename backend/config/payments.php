<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | "paymob" for real payments. "fake" is a local test checkout (Pay / Fail
    | buttons) that needs no Paymob account; it refuses to run when
    | APP_ENV=production, whatever this says (see PaymentGatewayManager).
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    'currency' => 'EGP',

    'paymob' => [
        // Egypt. Other regions have their own hosts (e.g. ksa.paymob.com).
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        // Unified Checkout for Egypt (?publicKey=...&clientSecret=... is appended).
        'checkout_url' => env('PAYMOB_CHECKOUT_URL', 'https://eg.checkout.paymob.com/'),

        // Dashboard → Settings → Account info. Test and live keys differ.
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        // The legacy API key, only for the transaction inquiry used by payments:reconcile.
        'api_key' => env('PAYMOB_API_KEY'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),

        // Comma-separated integration IDs offered at checkout (cards, mobile wallets).
        'integration_ids' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('PAYMOB_INTEGRATION_IDS', '')),
        ))),

        'timeout' => 15,
    ],

    'fake' => [
        // Signs the fake checkout's callbacks when PAYMOB_HMAC_SECRET is empty.
        'hmac_secret' => 'local-fake-gateway-secret',
    ],

];
