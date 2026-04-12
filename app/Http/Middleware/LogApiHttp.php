<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes one structured line per /api/* request to the "api" log channel when
 * logging.log_api_http is true (see config/logging.php, LOG_API_HTTP).
 *
 * Passwords, tokens, and webhook bodies are never logged in full.
 */
class LogApiHttp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*') || app()->runningUnitTests()) {
            return $next($request);
        }

        if (! config('logging.log_api_http')) {
            return $next($request);
        }

        $started = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        $payload = [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'ms' => $durationMs,
            'request_id' => $request->attributes->get('request_id'),
            'client' => $this->clientHint($request),
            'request' => $this->safeRequestPayload($request),
            'response' => $this->safeResponseSummary($response),
        ];

        if (is_array(config('logging.channels.api'))) {
            Log::channel('api')->info('API HTTP', $payload);
        } else {
            Log::info('API HTTP', $payload);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeRequestPayload(Request $request): array
    {
        if ($request->is('api/*/webhooks/*')) {
            return ['note' => 'webhook body omitted'];
        }

        if ($request->is('api/v1/login') && $request->isMethod('POST')) {
            return [
                'email' => $request->input('email'),
                'has_password' => $request->filled('password'),
            ];
        }

        if ($request->is('api/v1/register') && $request->isMethod('POST')) {
            return [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'has_password' => $request->filled('password'),
            ];
        }

        if ($request->is('api/v1/auth/google') && $request->isMethod('POST')) {
            return ['has_id_token' => $request->filled('id_token')];
        }

        if ($request->getQueryString() !== '') {
            return ['query' => $request->query()];
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeResponseSummary(Response $response): ?array
    {
        $code = $response->getStatusCode();
        if ($code < 400) {
            if ($code === 200 && str_contains((string) $response->headers->get('Content-Type'), 'json')) {
                return ['shape' => 'json_ok'];
            }

            return ['ok' => true];
        }

        $content = $response->getContent();
        $decoded = json_decode((string) $content, true);
        if (! is_array($decoded)) {
            return ['error' => mb_substr((string) $content, 0, 300)];
        }

        unset($decoded['token'], $decoded['user']);

        return $decoded;
    }

    private function clientHint(Request $request): ?string
    {
        $ua = $request->userAgent();

        return is_string($ua) && $ua !== '' ? mb_substr($ua, 0, 160) : null;
    }
}
