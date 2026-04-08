<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\MyLearningOverviewService;
use Illuminate\View\View;

class MyLearningController extends Controller
{
    public function __construct(
        private MyLearningOverviewService $overview,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $courses = $this->overview->coursesForUser($user);

        $memberAndEnrollmentTenantIds = $user->enrollments()->pluck('tenant_id')
            ->merge($user->memberships()->pluck('tenant_id'))
            ->unique()
            ->values();

        $discoverSpaces = Tenant::query()
            ->where(function ($q): void {
                $q->where('status', Tenant::STATUS_ACTIVE)
                    ->orWhereNull('status');
            })
            ->catalogDiscoverable()
            ->when($memberAndEnrollmentTenantIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $memberAndEnrollmentTenantIds))
            ->withCount([
                'programs as published_programs_count' => function ($q): void {
                    $q->where('is_published', true);
                },
                'courses as published_standalone_courses_count' => function ($q): void {
                    $q->where('is_published', true)->whereNull('program_id');
                },
            ])
            ->orderBy('name')
            ->get();

        $demoTenant = Tenant::query()->where('slug', 'adeola')->first();

        return view('my-learning.index', [
            'courses' => $courses,
            'discoverSpaces' => $discoverSpaces,
            'demoTenant' => $demoTenant,
        ]);
    }
}
