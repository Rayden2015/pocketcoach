<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function upsert(Request $request, Tenant $tenant, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $lesson->load(['module.course', 'course']);
        $course = $lesson->module?->course ?? $lesson->course;
        if ($course === null || $course->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Invalid lesson.'], 422);
        }

        if (! $this->access->canAccessCourse($request->user(), $course)) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        $validated = $request->validate([
            'notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'notes_is_public' => ['sometimes', 'boolean'],
            'position_seconds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $attributes = [
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ];

        $values = [];
        if (array_key_exists('notes', $validated)) {
            $values['notes'] = $validated['notes'];
        }
        if ($request->has('notes_is_public')) {
            $notes = $values['notes'] ?? LessonProgress::query()->where($attributes)->value('notes');
            $values['notes_is_public'] = ($notes !== null && trim((string) $notes) !== '') && $request->boolean('notes_is_public');
        } elseif (array_key_exists('notes', $validated) && ($validated['notes'] === null || trim((string) $validated['notes']) === '')) {
            $values['notes_is_public'] = false;
        }
        if (array_key_exists('position_seconds', $validated)) {
            $values['position_seconds'] = $validated['position_seconds'];
        }
        if (array_key_exists('completed', $validated)) {
            $values['completed_at'] = $validated['completed'] ? now() : null;
        }

        if ($values === []) {
            return response()->json(['message' => 'Provide notes, notes_is_public, position_seconds, and/or completed.'], 422);
        }

        $progress = LessonProgress::query()->updateOrCreate($attributes, $values);

        return response()->json([
            'data' => [
                'lesson_id' => $progress->lesson_id,
                'completed_at' => $progress->completed_at?->toIso8601String(),
                'notes' => $progress->notes,
                'notes_is_public' => $progress->notes_is_public,
                'position_seconds' => $progress->position_seconds,
            ],
        ]);
    }
}
