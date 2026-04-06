<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ContinueLearningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContinueLearningController extends Controller
{
    public function __construct(
        private ContinueLearningService $continueLearning,
    ) {}

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $next = $this->continueLearning->nextForUserInTenant($request->user(), $tenant->id);

        if ($next === null) {
            return response()->json(['data' => null]);
        }

        $lesson = $next['lesson'];
        $course = $next['course'];
        $progress = $next['progress'];

        return response()->json([
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                ],
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'lesson_type' => $lesson->lesson_type,
                    'body' => $lesson->body,
                    'media_url' => $lesson->media_url,
                    'meta' => $lesson->meta,
                ],
                'progress' => $progress === null ? null : [
                    'completed_at' => $progress->completed_at?->toIso8601String(),
                    'notes' => $progress->notes,
                    'position_seconds' => $progress->position_seconds,
                ],
            ],
        ]);
    }
}
