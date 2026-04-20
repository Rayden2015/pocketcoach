<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use App\Models\ReflectionPrompt;
use App\Models\Tenant;
use App\Services\Booking\BookingSlotService;
use App\Services\TenantEngagementSettings;
use Illuminate\View\View;

class PublicCatalogController extends Controller
{
    public function __construct(
        private BookingSlotService $bookingSlots,
    ) {}

    public function show(Tenant $tenant): View
    {
        $catalogSettings = TenantEngagementSettings::catalog($tenant);

        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->with([
                'courses' => function ($q): void {
                    $q->where('is_published', true)
                        ->orderByDesc('is_featured')
                        ->orderByDesc('catalog_view_count')
                        ->orderBy('sort_order');
                },
            ])
            ->when(
                $catalogSettings['show_featured_first'],
                fn ($q) => $q->orderByDesc('is_featured'),
            )
            ->orderBy('sort_order')
            ->get();

        $standaloneCourses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('program_id')
            ->where('is_published', true)
            ->when(
                $catalogSettings['show_featured_first'],
                fn ($q) => $q->orderByDesc('is_featured'),
            )
            ->orderByDesc('catalog_view_count')
            ->orderBy('sort_order')
            ->get();

        $reflectionCfg = TenantEngagementSettings::reflections($tenant);
        $latestReflection = null;
        if ($reflectionCfg['enabled']) {
            $latestReflection = ReflectionPrompt::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->first();
        }

        return view('public.catalog', [
            'tenant' => $tenant,
            'programs' => $programs,
            'standaloneCourses' => $standaloneCourses,
            'catalogIntroMarkdown' => $catalogSettings['intro_markdown'] ?? null,
            'trackCatalogViews' => $catalogSettings['track_catalog_views'],
            'reflectionsEnabled' => $reflectionCfg['enabled'],
            'latestReflection' => $latestReflection,
            'bookingAvailable' => $this->bookingSlots->bookableCoaches($tenant)->isNotEmpty(),
        ]);
    }
}
