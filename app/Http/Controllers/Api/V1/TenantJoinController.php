<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantJoinController extends Controller
{
    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $existing = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing !== null) {
            return response()->json([
                'message' => 'Already a member of this space.',
                'membership_id' => $existing->id,
                'role' => $existing->role,
            ]);
        }

        $membership = TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'role' => 'learner',
        ]);

        return response()->json([
            'message' => 'Joined space.',
            'membership_id' => $membership->id,
            'role' => $membership->role,
        ], 201);
    }
}
