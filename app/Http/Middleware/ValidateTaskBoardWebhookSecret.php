<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTaskBoardWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('task_board.incoming_secret');
        if (! is_string($secret) || $secret === '') {
            abort(404);
        }

        $given = $request->bearerToken()
            ?? $request->header('X-Task-Board-Secret');

        if (! is_string($given) || ! hash_equals($secret, $given)) {
            abort(403);
        }

        return $next($request);
    }
}
