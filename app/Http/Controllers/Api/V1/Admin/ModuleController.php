<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $query = Module::query()->where('tenant_id', $tenant->id)->orderBy('sort_order');

        if ($request->filled('course_id')) {
            $courseId = (int) $request->query('course_id');
            Course::query()->where('tenant_id', $tenant->id)->whereKey($courseId)->firstOrFail();
            $query->where('course_id', $courseId);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $course = Course::query()->where('tenant_id', $tenant->id)->whereKey($validated['course_id'])->firstOrFail();

        $baseSlug = Str::slug($validated['title']);
        $slug = $this->uniqueModuleSlug($course->id, $baseSlug);

        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json(['data' => $module], 201);
    }

    public function show(Tenant $tenant, Module $module): JsonResponse
    {
        abort_unless($module->tenant_id === $tenant->id, 404);

        return response()->json(['data' => $module]);
    }

    public function update(Request $request, Tenant $tenant, Module $module): JsonResponse
    {
        abort_unless($module->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'course_id' => ['sometimes', 'integer', Rule::exists('courses', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('modules', 'slug')
                    ->where(fn ($q) => $q->where('course_id', $module->course_id))
                    ->ignore($module->id),
            ],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['course_id'])) {
            $module->course_id = $validated['course_id'];
        }

        $module->fill(collect($validated)->except('course_id')->all());
        $module->save();

        return response()->json(['data' => $module->fresh()]);
    }

    public function destroy(Tenant $tenant, Module $module): JsonResponse
    {
        abort_unless($module->tenant_id === $tenant->id, 404);
        $module->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function uniqueModuleSlug(int $courseId, string $base): string
    {
        $slug = $base;
        $i = 1;
        while (Module::query()->where('course_id', $courseId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
