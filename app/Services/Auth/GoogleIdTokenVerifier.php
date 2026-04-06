<?php

namespace App\Services\Auth;

use Google\Client as GoogleClient;
use InvalidArgumentException;

class GoogleIdTokenVerifier
{
    public function verify(string $idToken): array
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            throw new \RuntimeException('Google OAuth is not configured (GOOGLE_CLIENT_ID).');
        }

        $client = new GoogleClient(['client_id' => $clientId]);
        $payload = $client->verifyIdToken($idToken);
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Invalid Google ID token.');
        }

        return $payload;
    }
}
