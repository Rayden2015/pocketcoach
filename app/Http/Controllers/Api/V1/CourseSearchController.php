<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseSearchController extends Controller
{
    /**
     * Search published courses across tenants the user can access (enrollments + memberships).
     * Mirrors web {@see \App\Http\Controllers\Web\CourseSearchController}.
     */
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $q = $q !== '' ? mb_substr($q, 0, 120) : '';

        $tenantIds = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->pluck('tenant_id')
            ->merge($request->user()->memberships()->pluck('tenant_id'))
            ->unique()
            ->values();

        if ($q === '' || mb_strlen($q) < 2 || $tenantIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
        $courses = Course::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('is_published', true)
            ->where(function ($query) use ($needle): void {
                $query->where('title', 'like', $needle)
                    ->orWhere('summary', 'like', $needle);
            })
            ->with(['tenant:id,slug,name', 'program:id,title'])
            ->orderBy('title')
            ->limit(40)
            ->get();

        $data = $courses->map(function (Course $course): array {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'summary' => $course->summary,
                'tenant_slug' => $course->tenant?->slug,
                'tenant_name' => $course->tenant?->name,
                'program_title' => $course->program?->title,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }
}
