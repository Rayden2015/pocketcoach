<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantHomeDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeDashboardController extends Controller
{
    public function __construct(
        private TenantHomeDashboardService $dashboard,
    ) {}

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $payload = $this->dashboard->build($request->user(), $tenant);

        return response()->json(['data' => $payload]);
    }
}
