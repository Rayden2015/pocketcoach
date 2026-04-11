<?php

namespace App\Http\Controllers\Web;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Coach\CoachSpaceSnapshotBuilder;
use Illuminate\Support\Collection;
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

        $leadIdSet = collect($staffTenantIds)->flip();

        /** @var Collection<int, Tenant> $spacesYouLead */
        $spacesYouLead = $tenants->filter(fn (Tenant $t) => $leadIdSet->has($t->id))->values();

        /** @var Collection<int, Tenant> $spacesYouLearn */
        $spacesYouLearn = $tenants->filter(fn (Tenant $t) => ! $leadIdSet->has($t->id))->values();

        $aggregate = [
            'spaces_led' => count($staffTenantIds),
            'learners_with_enrollment' => 0,
            'active_enrollments' => 0,
            'lesson_completions_7d' => 0,
            'programs_live' => 0,
        ];

        foreach ($staffTenantIds as $tid) {
            $snap = $coachSnapshots[$tid] ?? null;
            if ($snap === null) {
                continue;
            }
            $aggregate['learners_with_enrollment'] += (int) $snap['learners_with_enrollment'];
            $aggregate['active_enrollments'] += (int) $snap['active_enrollments'];
            $aggregate['lesson_completions_7d'] += (int) $snap['lesson_completions_7d'];
            $aggregate['programs_live'] += (int) $snap['programs_live'];
        }

        return view('my-coaching.index', [
            'tenants' => $tenants,
            'spacesYouLead' => $spacesYouLead,
            'spacesYouLearn' => $spacesYouLearn,
            'memberships' => $memberships,
            'coachSnapshots' => $coachSnapshots,
            'aggregateCoachStats' => $aggregate,
        ]);
    }
}
