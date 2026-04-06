<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $programId = $request->integer('program_id');
        $program = Program::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($programId)
            ->firstOrFail();

        $courses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->where('program_id', $program->id)
            ->orderBy('sort_order')
            ->get();

        return view('coach.courses.index', [
            'tenant' => $tenant,
            'program' => $program,
            'courses' => $courses,
        ]);
    }

    public function create(Request $request, Tenant $tenant): View
    {
        $program = Program::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($request->integer('program_id'))
            ->firstOrFail();

        return view('coach.courses.create', [
            'tenant' => $tenant,
            'program' => $program,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $programIdForSlug = (int) $request->input('program_id');

        $validated = $request->validate([
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('courses', 'slug')->where(fn ($q) => $q->where('program_id', $programIdForSlug)),
            ],
            'summary' => ['nullable', 'string', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $program = Program::query()->where('tenant_id', $tenant->id)->whereKey($validated['program_id'])->firstOrFail();
        $slug = ! empty($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->uniqueCourseSlug($program->id, Str::slug($validated['title']));

        Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $program->id])
            ->with('status', 'Course created.');
    }

    public function edit(Tenant $tenant, Course $course): View
    {
        abort_unless($course->tenant_id === $tenant->id, 404);
        $course->load('program');

        return view('coach.courses.edit', [
            'tenant' => $tenant,
            'course' => $course,
        ]);
    }

    public function update(Request $request, Tenant $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        $programIdForSlug = (int) $request->input('program_id');

        $validated = $request->validate([
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'slug')
                    ->where(fn ($q) => $q->where('program_id', $programIdForSlug))
                    ->ignore($course->id),
            ],
            'summary' => ['nullable', 'string', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $course->program_id = $validated['program_id'];
        $course->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);
        $course->save();

        return redirect()
            ->route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $course->program_id])
            ->with('status', 'Course updated.');
    }

    public function destroy(Tenant $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);
        $programId = $course->program_id;
        $course->delete();

        return redirect()
            ->route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $programId])
            ->with('status', 'Course deleted.');
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
