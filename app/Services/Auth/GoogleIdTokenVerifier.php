<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class GoogleIdTokenVerifier
{
    private const JWKS_URI = 'https://www.googleapis.com/oauth2/v3/certs';

    private const JWKS_CACHE_KEY = 'google.id_token_jwks';

    private const JWKS_TTL_SECONDS = 3600;

    /**
     * @return array<string, mixed>
     */
    public function verify(string $idToken): array
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            throw new \RuntimeException('Google OAuth is not configured (GOOGLE_CLIENT_ID).');
        }

        if (substr_count($idToken, '.') !== 2) {
            throw new InvalidArgumentException('Invalid Google ID token.');
        }

        try {
            $jwks = Cache::remember(self::JWKS_CACHE_KEY, self::JWKS_TTL_SECONDS, function (): array {
                $response = Http::timeout(15)->get(self::JWKS_URI);
                $response->throw();
                $data = $response->json();
                if (! is_array($data)) {
                    throw new \RuntimeException('Invalid JWKS response.');
                }

                return $data;
            });

            $keys = JWK::parseKeySet($jwks, 'RS256');
            JWT::$leeway = 120;
            $decoded = JWT::decode($idToken, $keys);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Invalid Google ID token.', 0, $e);
        }

        $payload = json_decode(json_encode($decoded, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Invalid Google ID token.');
        }

        $iss = $payload['iss'] ?? null;
        $allowedIss = ['https://accounts.google.com', 'accounts.google.com'];
        if (! is_string($iss) || ! in_array($iss, $allowedIss, true)) {
            throw new InvalidArgumentException('Invalid Google ID token.');
        }

        $aud = $payload['aud'] ?? null;
        if (is_string($aud)) {
            if ($aud !== $clientId) {
                throw new InvalidArgumentException('Invalid Google ID token.');
            }
        } elseif (is_array($aud)) {
            if (! in_array($clientId, $aud, true)) {
                throw new InvalidArgumentException('Invalid Google ID token.');
            }
        } else {
            throw new InvalidArgumentException('Invalid Google ID token.');
        }

        return $payload;
    }
}
