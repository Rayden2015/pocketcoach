<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

use function Sentry\configureScope;

/**
 * Enriches Sentry scope after AssignRequestId so errors include correlation and tenant tags.
 */
class SyncSentryScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! empty(config('sentry.dsn'))) {
            configureScope(function (Scope $scope) use ($request): void {
                $id = $request->attributes->get('request_id');
                if ($id !== null) {
                    $scope->setTag('request_id', (string) $id);
                }

                $tenant = $request->route('tenant');
                if ($tenant instanceof Tenant) {
                    $scope->setTag('tenant_id', (string) $tenant->id);
                    $scope->setTag('tenant_slug', (string) $tenant->slug);
                }
            });
        }

        return $next($request);
    }
}
