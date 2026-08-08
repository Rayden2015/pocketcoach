<?php

namespace App\Services;

use App\Models\ReflectionPrompt;
use App\Models\Tenant;

final class LatestReflectionPromptService
{
    public function forTenant(Tenant $tenant): ?ReflectionPrompt
    {
        if (! TenantEngagementSettings::reflections($tenant)['enabled']) {
            return null;
        }

        return ReflectionPrompt::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->first();
    }
}
