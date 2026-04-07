<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearnSpaceJoinController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();

        $existing = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            return redirect()
                ->route('learn.catalog', $tenant)
                ->with('status', 'You are already a member of this space.');
        }

        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        return redirect()
            ->route('learn.catalog', $tenant)
            ->with('status', 'You joined this space as a learner. Open a course below to enroll when free access is available.');
    }
}
