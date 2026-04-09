<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LessonController extends Controller
{
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $query = Lesson::query()->where('tenant_id', $tenant->id)->orderBy('sort_order');

        if ($request->filled('module_id')) {
            $moduleId = (int) $request->query('module_id');
            Module::query()->where('tenant_id', $tenant->id)->whereKey($moduleId)->firstOrFail();
            $query->where('module_id', $moduleId);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'lesson_type' => ['sometimes', 'string', 'max:32'],
            'body' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'meta' => ['nullable', 'array'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $module = Module::query()->where('tenant_id', $tenant->id)->whereKey($validated['module_id'])->firstOrFail();

        $baseSlug = Str::slug($validated['title']);
        $slug = $this->uniqueLessonSlug((int) $module->course_id, $baseSlug);

        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $module->course_id,
            'module_id' => $module->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'lesson_type' => $validated['lesson_type'] ?? 'text',
            'body' => $validated['body'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'meta' => $validated['meta'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json(['data' => $lesson], 201);
    }

    public function show(Tenant $tenant, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        return response()->json(['data' => $lesson]);
    }

    public function update(Request $request, Tenant $tenant, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'module_id' => ['sometimes', 'integer', Rule::exists('modules', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('lessons', 'slug')
                    ->where(fn ($q) => $q->where('course_id', $lesson->course_id))
                    ->ignore($lesson->id),
            ],
            'lesson_type' => ['sometimes', 'string', 'max:32'],
            'body' => ['sometimes', 'nullable', 'string'],
            'media_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['module_id'])) {
            $newModule = Module::query()->where('tenant_id', $tenant->id)->whereKey($validated['module_id'])->firstOrFail();
            if ($newModule->course_id !== $lesson->course_id) {
                throw ValidationException::withMessages([
                    'module_id' => 'Module must belong to the same course as the lesson.',
                ]);
            }
            $lesson->module_id = $newModule->id;
        }

        $lesson->fill(collect($validated)->except('module_id')->all());
        $lesson->save();

        return response()->json(['data' => $lesson->fresh()]);
    }

    public function destroy(Tenant $tenant, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);
        $lesson->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function uniqueLessonSlug(int $courseId, string $base): string
    {
        $slug = $base;
        $i = 1;
        while (Lesson::query()->where('course_id', $courseId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
