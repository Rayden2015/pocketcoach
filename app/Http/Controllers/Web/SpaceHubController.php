<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
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
        $demo = Tenant::query()->where('slug', 'adeola')->first();

        return view('dashboard', [
            'tenants' => $tenants,
            'memberships' => $memberships,
            'demoTenant' => $demo,
        ]);
    }
}
