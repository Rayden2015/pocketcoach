<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Tenant;
use App\Services\TenantEngagementSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicCatalogTrackController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $course = Course::query()->whereKey($validated['course_id'])->firstOrFail();
        abort_unless($course->tenant_id === $tenant->id, 404);
        abort_unless($course->is_published, 404);

        if (TenantEngagementSettings::catalogTrackViews($tenant)) {
            $course->increment('catalog_view_count');
        }

        return $this->redirectAfterTrack($request, $tenant, $course);
    }

    private function redirectAfterTrack(Request $request, Tenant $tenant, Course $course): RedirectResponse
    {
        $intended = $request->string('redirect_to')->toString();
        if ($intended !== '' && str_starts_with($intended, '/') && ! str_contains($intended, "\n")) {
            return redirect()->to($intended);
        }

        if ($request->user() !== null) {
            return redirect()->route('learn.course', [$tenant, $course]);
        }

        return redirect()->route('space.login', $tenant)
            ->with('status', 'Log in to open this course.');
    }
}
