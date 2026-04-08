<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\FreeProductLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(
        Request $request,
        Tenant $tenant,
        CourseAccessService $access,
        FreeProductLookup $freeProducts,
    ): JsonResponse {
        $user = $request->user();
        $accessibleIds = collect($access->accessibleCourseIdsForUserInTenant($user, $tenant->id))
            ->flip();

        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->with([
                'courses' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        $mapCourse = function ($c) use ($accessibleIds, $freeProducts, $tenant) {
            $freeId = $freeProducts->productIdForCourse($tenant, $c);

            return [
                'id' => $c->id,
                'title' => $c->title,
                'slug' => $c->slug,
                'summary' => $c->summary,
                'is_enrolled' => $accessibleIds->has($c->id),
                'free_product_id' => $freeId,
            ];
        };

        $data = $programs->map(fn (Program $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'summary' => $p->summary,
            'courses' => $p->courses->map($mapCourse),
        ]);

        $standalone = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('program_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        if ($standalone->isNotEmpty()) {
            $data->push([
                'id' => 0,
                'title' => 'Single courses',
                'slug' => '_standalone',
                'summary' => null,
                'courses' => $standalone->map($mapCourse),
            ]);
        }

        return response()->json([
            'data' => $data->values()->all(),
        ]);
    }
}
