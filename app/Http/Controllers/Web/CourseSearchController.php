<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseSearchController extends Controller
{
    public function index(Request $request): View
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

        $courses = collect();

        if ($q !== '' && mb_strlen($q) >= 2 && $tenantIds->isNotEmpty()) {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $courses = Course::query()
                ->whereIn('tenant_id', $tenantIds)
                ->where('is_published', true)
                ->where(function ($query) use ($needle): void {
                    $query->where('title', 'like', $needle)
                        ->orWhere('summary', 'like', $needle);
                })
                ->with(['tenant', 'program'])
                ->orderBy('title')
                ->limit(40)
                ->get();
        }

        return view('search.courses', [
            'query' => $q,
            'courses' => $courses,
            'tenantCount' => $tenantIds->count(),
        ]);
    }
}
