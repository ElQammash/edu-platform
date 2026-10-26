<?php

namespace App\Payments;

final readonly class CheckoutSession
{
    public function __construct(
        public string $url,
        public ?string $gatewayOrderId = null,
    ) {}
}
