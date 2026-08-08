<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Users who should receive space-wide learner communications (reflections, announcements).
 */
final class TenantLearnerAudienceService
{
    /**
     * Space members plus anyone with an active enrollment, deduplicated by user id.
     *
     * @return Builder<User>
     */
    public function queryForTenant(int $tenantId): Builder
    {
        $memberIds = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->pluck('user_id');

        $enrolledIds = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->pluck('user_id');

        $ids = $memberIds->merge($enrolledIds)->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return User::query()->whereRaw('0 = 1');
        }

        return User::query()->whereIn('id', $ids);
    }

    /**
     * @return Collection<int, User>
     */
    public function chunkIdsForTenant(int $tenantId): Collection
    {
        return $this->queryForTenant($tenantId)->pluck('id');
    }
}
