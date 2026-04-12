<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route HTTP /api/* application logging to the dedicated "api" daily channel
 * (see config/logging.php — files like api-{APP_ENV}-YYYY-MM-DD.log).
 */
class UseApiLogChannel
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') && ! app()->runningUnitTests() && $this->apiChannelConfigured()) {
            Log::setDefaultDriver('api');
        }

        return $next($request);
    }

    private function apiChannelConfigured(): bool
    {
        return is_array(config('logging.channels.api'));
    }
}
