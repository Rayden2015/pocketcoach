<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerCourseController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function show(Request $request, Tenant $tenant, Course $course): JsonResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse($request->user(), $course)) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
        ]);

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
                    'lessons' => $m->lessons->map(fn ($l) => [
                        'id' => $l->id,
                        'title' => $l->title,
                        'slug' => $l->slug,
                        'lesson_type' => $l->lesson_type,
                        'body' => $l->body,
                        'media_url' => $l->media_url,
                        'meta' => $l->meta,
                    ]),
                ]),
            ],
        ]);
    }
}
