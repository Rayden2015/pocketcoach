<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to suspended spaces on tenant-scoped routes.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');
        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        if ($tenant->isActive()) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return response()->json(['message' => 'This space is not available.'], 404);
        }

        abort(404, 'This space is not available.');
    }
}
