<?php

namespace App\Http\Middleware;

use App\Enums\TenantRole;
use App\Models\TenantMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');
        $user = $request->user();
        if ($tenant === null || $user === null) {
            return $request->is('api/*')
                ? response()->json(['message' => 'Unauthorized.'], 403)
                : abort(403, 'Unauthorized');
        }

        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null || ! in_array($membership->role, TenantRole::staffValues(), true)) {
            return $request->is('api/*')
                ? response()->json(['message' => 'Forbidden.'], 403)
                : abort(403, 'You do not have staff access for this space.');
        }

        return $next($request);
    }
}
