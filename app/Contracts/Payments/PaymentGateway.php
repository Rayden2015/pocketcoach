<?php

namespace App\Contracts\Payments;

use App\Services\Payments\PaymentInitializationResult;

interface PaymentGateway
{
    public function initializeTransaction(
        string $email,
        int $amountMinor,
        string $currency,
        string $reference,
        ?string $callbackUrl = null,
    ): PaymentInitializationResult;
}
