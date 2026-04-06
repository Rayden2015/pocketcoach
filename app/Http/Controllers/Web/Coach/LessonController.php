<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $module = Module::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($request->integer('module_id'))
            ->with(['course.program'])
            ->firstOrFail();

        $lessons = Lesson::query()
            ->where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->orderBy('sort_order')
            ->get();

        return view('coach.lessons.index', [
            'tenant' => $tenant,
            'module' => $module,
            'lessons' => $lessons,
        ]);
    }

    public function create(Request $request, Tenant $tenant): View
    {
        $module = Module::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($request->integer('module_id'))
            ->with(['course.program'])
            ->firstOrFail();

        return view('coach.lessons.create', [
            'tenant' => $tenant,
            'module' => $module,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $moduleIdForSlug = (int) $request->input('module_id');

        $validated = $request->validate([
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('lessons', 'slug')->where(fn ($q) => $q->where('module_id', $moduleIdForSlug)),
            ],
            'lesson_type' => ['nullable', 'string', 'max:32'],
            'body' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $module = Module::query()->where('tenant_id', $tenant->id)->whereKey($validated['module_id'])->firstOrFail();
        $slug = ! empty($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->uniqueLessonSlug($module->id, Str::slug($validated['title']));

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'lesson_type' => $validated['lesson_type'] ?: 'text',
            'body' => $validated['body'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id])
            ->with('status', 'Lesson created.');
    }

    public function edit(Tenant $tenant, Lesson $lesson): View
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);
        $lesson->load(['module.course.program']);

        return view('coach.lessons.edit', [
            'tenant' => $tenant,
            'lesson' => $lesson,
        ]);
    }

    public function update(Request $request, Tenant $tenant, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $moduleIdForSlug = (int) $request->input('module_id');

        $validated = $request->validate([
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lessons', 'slug')
                    ->where(fn ($q) => $q->where('module_id', $moduleIdForSlug))
                    ->ignore($lesson->id),
            ],
            'lesson_type' => ['nullable', 'string', 'max:32'],
            'body' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $lesson->module_id = $validated['module_id'];
        $type = $validated['lesson_type'] ?? '';
        $lesson->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'lesson_type' => $type !== '' ? $type : $lesson->lesson_type,
            'body' => $validated['body'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);
        $lesson->save();

        return redirect()
            ->route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $lesson->module_id])
            ->with('status', 'Lesson updated.');
    }

    public function destroy(Tenant $tenant, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);
        $moduleId = $lesson->module_id;
        $lesson->delete();

        return redirect()
            ->route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $moduleId])
            ->with('status', 'Lesson deleted.');
    }

    private function uniqueLessonSlug(int $moduleId, string $base): string
    {
        $slug = $base;
        $i = 1;
        while (Lesson::query()->where('module_id', $moduleId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
