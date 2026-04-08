<?php

namespace App\Http\Controllers\Web;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Coach\CoachSpaceSnapshotBuilder;
use Illuminate\View\View;

class SpaceHubController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $tenantIds = $user->enrollments()->pluck('tenant_id')
            ->merge($user->memberships()->pluck('tenant_id'))
            ->unique()
            ->values();

        $tenants = Tenant::query()
            ->whereIn('id', $tenantIds)
            ->orderBy('name')
            ->get();

        $memberships = $user->memberships()->get()->keyBy('tenant_id');

        $staffTenantIds = $user->memberships()
            ->whereIn('role', TenantRole::staffValues())
            ->pluck('tenant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $coachSnapshots = $staffTenantIds !== []
            ? app(CoachSpaceSnapshotBuilder::class)->buildMany($staffTenantIds)
            : [];

        return view('dashboard', [
            'tenants' => $tenants,
            'memberships' => $memberships,
            'coachSnapshots' => $coachSnapshots,
        ]);
    }
}
