<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\FreeProductLookup;
use Illuminate\View\View;

class LearnCatalogController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
        private FreeProductLookup $freeProducts,
    ) {}

    public function index(Tenant $tenant): View
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->with([
                'courses' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        $user = auth()->user();
        $accessibleIds = collect($this->access->accessibleCourseIdsForUserInTenant($user, $tenant->id))->flip();

        $standaloneCourses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('program_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        $courseMeta = [];
        foreach ($programs as $program) {
            foreach ($program->courses as $course) {
                $courseMeta[$course->id] = [
                    'is_enrolled' => $accessibleIds->has($course->id),
                    'free_product_id' => $this->freeProducts->productIdForCourse($tenant, $course),
                ];
            }
        }
        foreach ($standaloneCourses as $course) {
            $courseMeta[$course->id] = [
                'is_enrolled' => $accessibleIds->has($course->id),
                'free_product_id' => $this->freeProducts->productIdForCourse($tenant, $course),
            ];
        }

        return view('learn.catalog', [
            'tenant' => $tenant,
            'programs' => $programs,
            'standaloneCourses' => $standaloneCourses,
            'courseMeta' => $courseMeta,
        ]);
    }
}
