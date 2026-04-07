<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\FreeProductLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerCourseController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
        private FreeProductLookup $freeProducts,
    ) {}

    public function show(Request $request, Tenant $tenant, Course $course): JsonResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse($request->user(), $course)) {
            $freeProductId = $this->freeProducts->productIdForCourse($tenant, $course);

            return response()->json([
                'message' => 'You are not enrolled in this course.',
                'free_product_id' => $freeProductId,
            ], 403);
        }

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
        ]);

        $lessonIds = $course->modules->flatMap(fn ($m) => $m->lessons->pluck('id'))->all();
        $progressByLesson = LessonProgress::query()
            ->where('user_id', $request->user()->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        return response()->json([
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'summary' => $course->summary,
                'modules' => $course->modules->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'slug' => $m->slug,
                    'lessons' => $m->lessons->map(function ($l) use ($progressByLesson) {
                        $pr = $progressByLesson->get($l->id);

                        return [
                            'id' => $l->id,
                            'title' => $l->title,
                            'slug' => $l->slug,
                            'lesson_type' => $l->lesson_type,
                            'body' => $l->body,
                            'media_url' => $l->resolvedMediaUrl(),
                            'meta' => $l->meta,
                            'progress' => $pr === null ? null : [
                                'completed_at' => $pr->completed_at?->toIso8601String(),
                                'notes' => $pr->notes,
                                'position_seconds' => $pr->position_seconds,
                            ],
                        ];
                    }),
                ]),
            ],
        ]);
    }
}
