<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $rawCourseId = $request->query('course_id');
        if ($rawCourseId === null || $rawCourseId === '' || (int) $rawCourseId < 1) {
            return $this->modulesHub($tenant);
        }

        $course = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey((int) $rawCourseId)
            ->with('program')
            ->firstOrFail();

        $modules = Module::query()
            ->where('tenant_id', $tenant->id)
            ->where('course_id', $course->id)
            ->orderBy('sort_order')
            ->get();

        return view('coach.modules.index', [
            'tenant' => $tenant,
            'course' => $course,
            'modules' => $modules,
        ]);
    }

    public function create(Request $request, Tenant $tenant): View|RedirectResponse
    {
        $rawCourseId = $request->query('course_id');
        if ($rawCourseId === null || $rawCourseId === '' || (int) $rawCourseId < 1) {
            return redirect()
                ->route('coach.modules.index', $tenant)
                ->with('status', 'Choose a course to add a module.');
        }

        $course = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey((int) $rawCourseId)
            ->with('program')
            ->firstOrFail();

        return view('coach.modules.create', [
            'tenant' => $tenant,
            'course' => $course,
        ]);
    }

    private function modulesHub(Tenant $tenant): View
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->with(['courses' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $standaloneCourses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('program_id')
            ->orderBy('sort_order')
            ->get();

        return view('coach.modules.hub', [
            'tenant' => $tenant,
            'programs' => $programs,
            'standaloneCourses' => $standaloneCourses,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $courseIdForSlug = (int) $request->input('course_id');

        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('modules', 'slug')->where(fn ($q) => $q->where('course_id', $courseIdForSlug)),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $course = Course::query()->where('tenant_id', $tenant->id)->whereKey($validated['course_id'])->firstOrFail();
        $slug = ! empty($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->uniqueModuleSlug($course->id, Str::slug($validated['title']));

        Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id])
            ->with('status', 'Module created.');
    }

    public function edit(Tenant $tenant, Module $module): View
    {
        abort_unless($module->tenant_id === $tenant->id, 404);
        $module->load(['course.program']);

        return view('coach.modules.edit', [
            'tenant' => $tenant,
            'module' => $module,
        ]);
    }

    public function update(Request $request, Tenant $tenant, Module $module): RedirectResponse
    {
        abort_unless($module->tenant_id === $tenant->id, 404);

        $courseIdForSlug = (int) $request->input('course_id');

        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('modules', 'slug')
                    ->where(fn ($q) => $q->where('course_id', $courseIdForSlug))
                    ->ignore($module->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $module->course_id = $validated['course_id'];
        $module->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);
        $module->save();

        return redirect()
            ->route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $module->course_id])
            ->with('status', 'Module updated.');
    }

    public function destroy(Tenant $tenant, Module $module): RedirectResponse
    {
        abort_unless($module->tenant_id === $tenant->id, 404);
        $courseId = $module->course_id;
        $module->delete();

        return redirect()
            ->route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $courseId])
            ->with('status', 'Module deleted.');
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
