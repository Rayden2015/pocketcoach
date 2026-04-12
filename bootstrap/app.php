<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantStaff;
use App\Http\Middleware\LogApiHttp;
use App\Http\Middleware\SyncSentryScope;
use App\Http\Middleware\UseApiLogChannel;
use App\Http\Middleware\ValidateTaskBoardWebhookSecret;
use App\Models\Tenant;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([
            AssignRequestId::class,
            UseApiLogChannel::class,
            LogApiHttp::class,
            SyncSentryScope::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            $tenant = $request->route('tenant');
            if ($tenant instanceof Tenant) {
                return route('space.login', $tenant);
            }

            return route('login');
        });
        $middleware->redirectUsersTo(fn () => route('my-coaching'));

        $middleware->alias([
            'tenant.staff' => EnsureTenantStaff::class,
            'super_admin' => EnsureSuperAdmin::class,
            'task_board.webhook' => ValidateTaskBoardWebhookSecret::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Web UIs often use axios with X-Requested-With: XMLHttpRequest, which makes
         * Request::expectsJson() true even for same-origin session routes. The default
         * Authenticate middleware then omits a redirect URL and the exception handler
         * returns JSON {"message":"Unauthenticated."} instead of sending guests to login.
         *
         * API routes (/api/*) keep JSON 401 for mobile and token clients.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }

            // Non-browser JSON clients (e.g. tests/getJson, HTTP clients) expect 401 JSON.
            // Browser XHR (axios) sets X-Requested-With; keep redirect so guests see login, not raw JSON.
            if ($request->wantsJson() && ! $request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 401);
            }

            return redirect()->guest($e->redirectTo($request) ?? route('login'))
                ->with('warning', 'Please sign in to continue.');
        });
    })->create();
