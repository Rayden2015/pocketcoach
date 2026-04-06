<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use Illuminate\Http\Client\RequestException;

class PaystackGateway implements PaymentGateway
{
    public function __construct(
        private PaystackClient $client,
    ) {}

    /**
     * @throws RequestException
     */
    public function initializeTransaction(
        string $email,
        int $amountMinor,
        string $currency,
        string $reference,
        ?string $callbackUrl = null,
    ): PaymentInitializationResult {
        $json = $this->client->initializeTransaction(
            $email,
            $amountMinor,
            $currency,
            $reference,
            $callbackUrl,
        );

        $data = $json['data'] ?? [];

        return new PaymentInitializationResult(
            authorizationUrl: (string) ($data['authorization_url'] ?? ''),
            accessCode: (string) ($data['access_code'] ?? ''),
            reference: (string) ($data['reference'] ?? $reference),
        );
    }
}
