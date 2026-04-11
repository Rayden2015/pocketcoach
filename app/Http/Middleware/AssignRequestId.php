<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds a stable request id for the HTTP lifecycle: Log::shareContext, response header,
 * and $request->attributes['request_id'] for support correlation.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->headers->get('X-Request-Id');
        if (! is_string($id) || preg_match('/^[a-zA-Z0-9\-_.]{8,128}$/', $id) !== 1) {
            $id = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $id);

        Log::shareContext([
            'request_id' => $id,
        ]);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
