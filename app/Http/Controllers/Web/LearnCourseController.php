<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\FreeProductLookup;
use Illuminate\View\View;

class LearnCourseController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
        private FreeProductLookup $freeProducts,
    ) {}

    public function show(Tenant $tenant, Course $course): View
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        $user = auth()->user();
        $canAccess = $this->access->canAccessCourse($user, $course);
        $freeProductId = $canAccess ? null : $this->freeProducts->productIdForCourse($tenant, $course);

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
        ]);

        $lessonIds = $course->modules->flatMap(fn ($m) => $m->lessons)->pluck('id');
        $completedLessonIds = collect();
        $lessonsTotal = $lessonIds->count();
        $lessonsCompleted = 0;
        if ($canAccess && $lessonIds->isNotEmpty()) {
            $completedLessonIds = LessonProgress::query()
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->whereNotNull('completed_at')
                ->pluck('lesson_id');
            $lessonsCompleted = $completedLessonIds->count();
        }

        $courseProgressPercent = $lessonsTotal > 0
            ? (int) round(100 * $lessonsCompleted / $lessonsTotal)
            : 0;

        return view('learn.course', [
            'tenant' => $tenant,
            'course' => $course,
            'canAccess' => $canAccess,
            'freeProductId' => $freeProductId,
            'completedLessonIds' => $completedLessonIds,
            'lessonsTotal' => $lessonsTotal,
            'lessonsCompleted' => $lessonsCompleted,
            'courseProgressPercent' => $courseProgressPercent,
        ]);
    }
}
