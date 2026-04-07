<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningSummaryController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $user = $request->user();
        $courseIds = $this->access->accessibleCourseIdsForUserInTenant($user, $tenant->id);

        if ($courseIds === []) {
            return response()->json(['data' => []]);
        }

        $courses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $courseIds)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        $rows = [];
        foreach ($courses as $course) {
            $lessonIds = Lesson::query()
                ->whereHas('module', fn ($q) => $q->where('course_id', $course->id)->where('is_published', true))
                ->where('is_published', true)
                ->pluck('id');

            $total = $lessonIds->count();
            $completed = $lessonIds->isEmpty()
                ? 0
                : LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('lesson_id', $lessonIds)
                    ->whereNotNull('completed_at')
                    ->count();

            $rows[] = [
                'course_id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'lessons_total' => $total,
                'lessons_completed' => $completed,
            ];
        }

        return response()->json(['data' => $rows]);
    }
}
