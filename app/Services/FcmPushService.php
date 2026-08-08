<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FcmPushService
{
    public function isConfigured(): bool
    {
        return filled(config('services.fcm.server_key'));
    }

    /**
     * @param  list<string>  $tokens
     * @param  array{title: string, body: string, data?: array<string, string>}  $payload
     */
    public function sendToTokens(array $tokens, array $payload): void
    {
        if (! $this->isConfigured() || $tokens === []) {
            return;
        }

        $serverKey = (string) config('services.fcm.server_key');

        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => array_values($chunk),
                    'notification' => [
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ],
                    'data' => $payload['data'] ?? [],
                ]);

                if (! $response->successful()) {
                    Log::warning('fcm.send_failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'token_count' => count($chunk),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('fcm.send_exception', [
                    'message' => $e->getMessage(),
                    'token_count' => count($chunk),
                ]);
            }
        }
    }

    public function sendToUser(int $userId, array $payload): void
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        $this->sendToTokens($tokens, $payload);
    }
}
