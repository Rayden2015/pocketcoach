<?php

namespace Tests\Support;

use App\Contracts\Payments\PaymentGateway;
use App\Services\Payments\PaymentInitializationResult;

class FakePaymentGateway implements PaymentGateway
{
    public function initializeTransaction(
        string $email,
        int $amountMinor,
        string $currency,
        string $reference,
        ?string $callbackUrl = null,
    ): PaymentInitializationResult {
        return new PaymentInitializationResult(
            authorizationUrl: 'https://checkout.paystack.test/'.$reference,
            accessCode: 'test_access_code',
            reference: $reference,
        );
    }
}
