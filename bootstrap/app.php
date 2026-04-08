<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantStaff;
use App\Http\Middleware\ValidateTaskBoardWebhookSecret;
use App\Models\Tenant;
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
        $middleware->redirectGuestsTo(function (Request $request) {
            $tenant = $request->route('tenant');
            if ($tenant instanceof Tenant) {
                return route('space.login', $tenant);
            }

            return route('login');
        });
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->alias([
            'tenant.staff' => EnsureTenantStaff::class,
            'super_admin' => EnsureSuperAdmin::class,
            'task_board.webhook' => ValidateTaskBoardWebhookSecret::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
