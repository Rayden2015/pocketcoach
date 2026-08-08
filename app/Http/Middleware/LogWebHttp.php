<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Structured web request logging for troubleshooting (coach console, public book, auth).
 * Enabled via logging.log_web_http (LOG_WEB_HTTP). Mutations always logged; GET only on 4xx/5xx.
 */
class LogWebHttp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') || app()->runningUnitTests()) {
            return $next($request);
        }

        if (! config('logging.log_web_http')) {
            return $next($request);
        }

        $started = microtime(true);
        $response = $next($request);
        $status = $response->getStatusCode();
        $isMutation = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if (! $isMutation && $status < 400) {
            return $response;
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        Log::info('Web HTTP', $this->payload($request, $response, $durationMs));

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, Response $response, int $durationMs): array
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        return [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'route' => $request->route()?->getName(),
            'status' => $response->getStatusCode(),
            'ms' => $durationMs,
            'request_id' => $request->attributes->get('request_id'),
            'user_id' => $user?->id,
            'tenant_id' => $tenant instanceof Tenant ? $tenant->id : null,
            'tenant_slug' => $tenant instanceof Tenant ? $tenant->slug : null,
            'request' => $this->safeRequestSummary($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function safeRequestSummary(Request $request): array
    {
        if ($request->routeIs('login', 'space.login') && $request->isMethod('POST')) {
            return [
                'email' => $request->input('email'),
                'has_password' => $request->filled('password'),
            ];
        }

        if ($request->routeIs('register', 'space.register') && $request->isMethod('POST')) {
            return [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'has_password' => $request->filled('password'),
            ];
        }

        if ($request->routeIs('public.book.store')) {
            return [
                'coach_user_id' => $request->input('coach_user_id'),
                'starts_at' => $request->input('starts_at'),
                'ends_at' => $request->input('ends_at'),
                'guest' => $request->user() === null,
                'has_guest_email' => $request->filled('guest_email'),
            ];
        }

        if ($request->routeIs('coach.bookings.confirm', 'coach.bookings.decline', 'coach.bookings.cancel', 'mail.booking.confirm', 'mail.booking.decline')) {
            $booking = $request->route('booking');

            return [
                'booking_id' => is_object($booking) ? $booking->getKey() : $booking,
                'route' => $request->route()?->getName(),
            ];
        }

        if ($request->routeIs('coach.team.invites.store') && $request->isMethod('POST')) {
            return [
                'email' => $request->input('email'),
                'role' => $request->input('role'),
            ];
        }

        if ($request->routeIs('coach.team.invites.destroy') && $request->isMethod('DELETE')) {
            $invite = $request->route('invite');

            return [
                'invite_id' => is_object($invite) ? $invite->getKey() : $invite,
            ];
        }

        if ($request->getQueryString() !== '') {
            return ['query' => $request->query()];
        }

        return [];
    }
}
