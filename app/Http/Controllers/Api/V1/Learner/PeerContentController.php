<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\TenantEngagementSettings;
use Illuminate\Http\JsonResponse;

class PeerContentController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function lessonPublicNotes(Tenant $tenant, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $lesson->load(['module.course', 'course']);
        $course = $lesson->module?->course ?? $lesson->course;
        if ($course === null || $course->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Invalid lesson.'], 422);
        }

        $user = request()->user();
        if (! $this->access->canAccessCourse($user, $course)) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        $rows = LessonProgress::query()
            ->where('lesson_id', $lesson->id)
            ->where('tenant_id', $tenant->id)
            ->where('notes_is_public', true)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->where('user_id', '!=', $user->id)
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (LessonProgress $row) => [
                'user' => [
                    'id' => $row->user?->id,
                    'name' => $row->user?->name,
                ],
                'notes' => $row->notes,
                'updated_at' => $row->updated_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function reflectionPublicResponses(Tenant $tenant, ReflectionPrompt $reflection_prompt): JsonResponse
    {
        abort_unless($reflection_prompt->tenant_id === $tenant->id, 404);
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);
        abort_unless($reflection_prompt->is_published && $reflection_prompt->published_at !== null, 404);

        $user = request()->user();

        $rows = ReflectionResponse::query()
            ->where('reflection_prompt_id', $reflection_prompt->id)
            ->where('is_public', true)
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->where('user_id', '!=', $user->id)
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (ReflectionResponse $row) => [
                'user' => [
                    'id' => $row->user?->id,
                    'name' => $row->user?->name,
                ],
                'body' => $row->body,
                'updated_at' => $row->updated_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
