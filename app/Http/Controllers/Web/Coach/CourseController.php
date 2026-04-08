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

    public function standaloneIndex(Tenant $tenant): View
    {
        $courses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('program_id')
            ->orderBy('sort_order')
            ->get();

        return view('coach.courses.standalone-index', [
            'tenant' => $tenant,
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

    public function standaloneCreate(Tenant $tenant): View
    {
        return view('coach.courses.create-standalone', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        [$validated, $program] = $this->validatedCoursePayload($request, $tenant, null);

        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program?->id,
            'title' => $validated['title'],
            'slug' => $this->nextUniqueSlug($tenant->id, $validated['slug'] ?? null, $validated['title'], null),
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return $this->redirectAfterSave($tenant, $course)
            ->with('status', 'Course created.');
    }

    public function edit(Tenant $tenant, Course $course): View
    {
        abort_unless($course->tenant_id === $tenant->id, 404);
        $course->load('program');

        return view('coach.courses.edit', [
            'tenant' => $tenant,
            'course' => $course,
            'programs' => Program::query()->where('tenant_id', $tenant->id)->orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        [$validated, $program] = $this->validatedCoursePayload($request, $tenant, $course);

        $slugInput = trim((string) ($validated['slug'] ?? ''));
        $slugBase = $slugInput !== '' ? $slugInput : $course->slug;
        $slug = $this->ensureUniqueSlug($tenant->id, $slugBase, $course->id);

        $course->program_id = $program?->id;
        $course->fill([
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
        ]);
        $course->save();

        return $this->redirectAfterSave($tenant, $course)
            ->with('status', 'Course updated.');
    }

    public function destroy(Tenant $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);
        $course->delete();

        return $this->redirectAfterSave($tenant, $course)
            ->with('status', 'Course deleted.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: ?Program}
     */
    private function validatedCoursePayload(Request $request, Tenant $tenant, ?Course $existing): array
    {
        $ign = $existing?->id;

        $validated = $request->validate([
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('courses', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenant->id))->ignore($ign),
            ],
            'summary' => ['nullable', 'string', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $program = null;
        if (! empty($validated['program_id'])) {
            $program = Program::query()->where('tenant_id', $tenant->id)->whereKey($validated['program_id'])->firstOrFail();
        }

        return [$validated, $program];
    }

    private function nextUniqueSlug(int $tenantId, ?string $slugInput, string $title, ?int $ignoreCourseId): string
    {
        $base = $slugInput !== null && $slugInput !== ''
            ? $slugInput
            : Str::slug($title);

        return $this->ensureUniqueSlug($tenantId, $base, $ignoreCourseId);
    }

    private function ensureUniqueSlug(int $tenantId, string $base, ?int $ignoreCourseId): string
    {
        $slug = $base;
        $i = 1;
        while (Course::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($ignoreCourseId !== null, fn ($q) => $q->where('id', '!=', $ignoreCourseId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function redirectAfterSave(Tenant $tenant, Course $course): RedirectResponse
    {
        if ($course->program_id !== null) {
            return redirect()->route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $course->program_id]);
        }

        return redirect()->route('coach.courses.standalone.index', $tenant);
    }
}
