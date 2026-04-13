<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantHomeDashboardService;
use Illuminate\View\View;

class LearnDashboardController extends Controller
{
    public function __construct(
        private TenantHomeDashboardService $dashboard,
    ) {}

    public function show(Tenant $tenant): View
    {
        $user = auth()->user();
        $data = $this->dashboard->build($user, $tenant);

        return view('learn.dashboard', [
            'tenant' => $tenant,
            'dashboard' => $data,
        ]);
    }
}
