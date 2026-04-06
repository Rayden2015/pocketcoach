<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class PaystackClient
{
    public function __construct(
        private string $secretKey,
        private string $baseUrl = 'https://api.paystack.co',
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
    ): array {
        $payload = array_filter([
            'email' => $email,
            'amount' => $amountMinor,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
        ], fn ($v) => $v !== null && $v !== '');

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->asJson()
            ->post('/transaction/initialize', $payload);

        $response->throw();

        return $response->json();
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        if ($this->secretKey === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $rawBody, $this->secretKey);

        return hash_equals($computed, $signature);
    }
}
