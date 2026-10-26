<?php

namespace App\Payments;

use RuntimeException;

/**
 * Payments cannot run safely with the current configuration (e.g. the fake
 * gateway in production, or Paymob keys missing). Never caught to fall
 * back to another gateway.
 */
class PaymentGatewayMisconfigured extends RuntimeException {}
