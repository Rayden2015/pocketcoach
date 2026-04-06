<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\View\View;

class LearnCourseController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function show(Tenant $tenant, Course $course): View
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
            abort(403, 'You need to enroll before opening this course.');
        }

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
        ]);

        return view('learn.course', [
            'tenant' => $tenant,
            'course' => $course,
        ]);
    }
}
