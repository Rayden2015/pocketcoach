<?php

namespace App\Http\Middleware;

use App\Enums\TenantRole;
use App\Models\TenantMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSpaceOwnerOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');
        $user = $request->user();
        if ($tenant === null || $user === null) {
            abort(403, 'Unauthorized');
        }

        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null || ! in_array($membership->role, TenantRole::ownerOrAdminValues(), true)) {
            abort(403, 'Only the space owner or an admin can manage the team.');
        }

        return $next($request);
    }
}
