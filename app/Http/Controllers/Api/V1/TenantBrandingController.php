<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

/**
 * Public read model for clients (web + mobile) to load theming without auth.
 */
class TenantBrandingController extends Controller
{
    public function show(Tenant $tenant): JsonResponse
    {
        if (! $tenant->isActive()) {
            return response()->json(['message' => 'Space not available.'], 404);
        }

        return response()->json([
            'data' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'branding' => $tenant->branding ?? [],
                'settings' => $tenant->settings ?? [],
            ],
        ]);
    }
}
