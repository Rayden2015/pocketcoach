<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $query = Course::query()->where('tenant_id', $tenant->id)->orderBy('sort_order');

        if ($request->filled('program_id')) {
            $programId = (int) $request->query('program_id');
            $program = Program::query()->where('tenant_id', $tenant->id)->whereKey($programId)->firstOrFail();
            $query->where('program_id', $program->id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:65535'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $program = Program::query()->where('tenant_id', $tenant->id)->whereKey($validated['program_id'])->firstOrFail();

        $baseSlug = Str::slug($validated['title']);
        $slug = $this->uniqueCourseSlug($program->id, $baseSlug);

        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json(['data' => $course], 201);
    }

    public function show(Tenant $tenant, Course $course): JsonResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        return response()->json(['data' => $course]);
    }

    public function update(Request $request, Tenant $tenant, Course $course): JsonResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'program_id' => ['sometimes', 'integer', Rule::exists('programs', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('courses', 'slug')
                    ->where(fn ($q) => $q->where('program_id', $course->program_id))
                    ->ignore($course->id),
            ],
            'summary' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['program_id'])) {
            $course->program_id = $validated['program_id'];
        }

        $course->fill(collect($validated)->except('program_id')->all());
        $course->save();

        return response()->json(['data' => $course->fresh()]);
    }

    public function destroy(Tenant $tenant, Course $course): JsonResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);
        $course->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function uniqueCourseSlug(int $programId, string $base): string
    {
        $slug = $base;
        $i = 1;
        while (Course::query()->where('program_id', $programId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
