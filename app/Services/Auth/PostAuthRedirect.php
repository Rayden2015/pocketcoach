<?php

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;

final class PostAuthRedirect
{
    /**
     * Default URL after global sign-in or sign-up (no tenant-prefixed session).
     * Uses one-space shortcut: continue learning or catalog for that space.
     */
    public static function defaultUrl(User $user): string
    {
        if ($user->is_super_admin) {
            return route('my-coaching');
        }

        // Coaches land on the hub first; learner shortcuts apply only when they are not on staff anywhere.
        if ($user->coachesAnySpace()) {
            return route('my-coaching');
        }

        $tenantIds = $user->enrollments()->pluck('tenant_id')
            ->merge($user->memberships()->pluck('tenant_id'))
            ->unique()
            ->values();

        if ($tenantIds->count() !== 1) {
            return route('my-learning');
        }

        $tenant = Tenant::query()->whereKey($tenantIds->first())->first();
        if ($tenant === null) {
            return route('my-learning');
        }

        $usable = $tenant->status === Tenant::STATUS_ACTIVE || $tenant->status === null;
        if (! $usable) {
            return route('my-learning');
        }

        return route('learn.continue', $tenant);
    }
}
