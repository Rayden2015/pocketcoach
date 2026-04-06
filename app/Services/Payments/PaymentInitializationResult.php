<?php

namespace App\Services\Payments;

readonly class PaymentInitializationResult
{
    public function __construct(
        public string $authorizationUrl,
        public string $accessCode,
        public string $reference,
    ) {}
}
