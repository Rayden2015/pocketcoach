<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $rawModuleId = $request->query('module_id');
        if ($rawModuleId !== null && $rawModuleId !== '' && (int) $rawModuleId >= 1) {
            $module = Module::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey((int) $rawModuleId)
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
                'courseForRoot' => null,
                'lessons' => $lessons,
                'listContext' => 'module',
            ]);
        }

        $rawCourseId = $request->query('course_id');
        if ($rawCourseId !== null && $rawCourseId !== '' && (int) $rawCourseId >= 1) {
            $course = Course::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey((int) $rawCourseId)
                ->with('program')
                ->firstOrFail();

            $lessons = Lesson::query()
                ->where('tenant_id', $tenant->id)
                ->where('course_id', $course->id)
                ->whereNull('module_id')
                ->orderBy('sort_order')
                ->get();

            return view('coach.lessons.index', [
                'tenant' => $tenant,
                'module' => null,
                'courseForRoot' => $course,
                'lessons' => $lessons,
                'listContext' => 'course',
            ]);
        }

        return $this->lessonsHub($tenant);
    }

    public function create(Request $request, Tenant $tenant): View|RedirectResponse
    {
        $rawModuleId = $request->query('module_id');
        if ($rawModuleId !== null && $rawModuleId !== '' && (int) $rawModuleId >= 1) {
            $module = Module::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey((int) $rawModuleId)
                ->with(['course.program'])
                ->firstOrFail();

            return view('coach.lessons.create', [
                'tenant' => $tenant,
                'module' => $module,
                'courseForRoot' => null,
            ]);
        }

        $rawCourseId = $request->query('course_id');
        if ($rawCourseId !== null && $rawCourseId !== '' && (int) $rawCourseId >= 1) {
            $course = Course::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey((int) $rawCourseId)
                ->with('program')
                ->firstOrFail();

            return view('coach.lessons.create', [
                'tenant' => $tenant,
                'module' => null,
                'courseForRoot' => $course,
            ]);
        }

        return redirect()
            ->route('coach.lessons.index', $tenant)
            ->with('status', 'Choose a module or a course to add a lesson.');
    }

    private function lessonsHub(Tenant $tenant): View
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'courses' => fn ($q) => $q->orderBy('sort_order'),
                'courses.modules' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        $standaloneCourses = Course::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('program_id')
            ->with(['modules' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('coach.lessons.hub', [
            'tenant' => $tenant,
            'programs' => $programs,
            'standaloneCourses' => $standaloneCourses,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'module_id' => ['nullable', 'integer', Rule::exists('modules', 'id')->where('tenant_id', $tenant->id)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],
            'lesson_type' => ['required', 'string', Rule::in(Lesson::TYPES)],
            'material_source' => ['required', 'string', Rule::in(['none', 'upload', 'url'])],
            'body' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:8192'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $hasModule = filled($validated['module_id'] ?? null);
        $hasCourse = filled($validated['course_id'] ?? null);
        if ($hasModule === $hasCourse) {
            throw ValidationException::withMessages([
                'module_id' => 'Choose either a module or a course (for lessons without a module), not both.',
            ]);
        }

        $moduleId = null;
        $courseId = null;
        if ($hasModule) {
            $module = Module::query()->where('tenant_id', $tenant->id)->whereKey($validated['module_id'])->firstOrFail();
            $moduleId = $module->id;
            $courseId = $module->course_id;
        } else {
            $course = Course::query()->where('tenant_id', $tenant->id)->whereKey($validated['course_id'])->firstOrFail();
            $courseId = $course->id;
        }

        if ($validated['lesson_type'] !== Lesson::TYPE_TEXT && $validated['material_source'] === 'none') {
            throw ValidationException::withMessages([
                'material_source' => 'Upload a file or add an external link for this lesson type.',
            ]);
        }

        if ($validated['material_source'] === 'url' && ! filled($validated['media_url'] ?? null)) {
            throw ValidationException::withMessages([
                'media_url' => 'Enter the URL where learners can access this material.',
            ]);
        }

        if ($validated['material_source'] === 'upload' && ! $request->hasFile('material_file')) {
            throw ValidationException::withMessages([
                'material_file' => 'Choose a file to upload.',
            ]);
        }

        if ($validated['material_source'] === 'upload' && $newFile = $request->file('material_file')) {
            $this->validateMaterialFileAgainstType($newFile, $validated['lesson_type']);
        }

        [$mediaUrl, $diskPath] = $this->materialPayloadFromRequest(
            $request,
            $tenant,
            $validated['material_source'],
            $validated['lesson_type'],
            null,
        );

        $slugBase = ! empty($validated['slug'] ?? null)
            ? $validated['slug']
            : Str::slug($validated['title']);
        $slug = $this->uniqueLessonSlugForCourse($courseId, $slugBase, null);

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'title' => $validated['title'],
            'slug' => $slug,
            'lesson_type' => $validated['lesson_type'],
            'body' => $validated['body'] ?? null,
            'media_url' => $mediaUrl,
            'media_disk_path' => $diskPath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->to($this->lessonsIndexUrl($tenant, $moduleId, $courseId, $hasModule ? 'module' : 'course'))
            ->with('status', 'Lesson created.');
    }

    public function edit(Tenant $tenant, Lesson $lesson): View
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);
        $lesson->load(['module.course.program', 'course.program']);

        return view('coach.lessons.edit', [
            'tenant' => $tenant,
            'lesson' => $lesson,
            'modulesInCourse' => Module::query()
                ->where('tenant_id', $tenant->id)
                ->where('course_id', $lesson->course_id)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $courseId = $lesson->course_id;

        $baseRules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lessons', 'slug')
                    ->where(fn ($q) => $q->where('course_id', $courseId))
                    ->ignore($lesson->id),
            ],
            'lesson_type' => ['required', 'string', Rule::in(Lesson::TYPES)],
            'material_source' => ['required', 'string', Rule::in(['none', 'upload', 'url'])],
            'body' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:8192'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ];

        if ($lesson->module_id !== null) {
            $validated = $request->validate(array_merge([
                'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('tenant_id', $tenant->id)],
            ], $baseRules));
            $module = Module::query()->where('tenant_id', $tenant->id)->whereKey($validated['module_id'])->firstOrFail();
            if ($module->course_id !== $lesson->course_id) {
                throw ValidationException::withMessages([
                    'module_id' => 'Module must belong to the same course.',
                ]);
            }
            $lesson->module_id = $module->id;
        } else {
            $validated = $request->validate($baseRules);
        }

        if ($validated['lesson_type'] !== Lesson::TYPE_TEXT && $validated['material_source'] === 'none') {
            throw ValidationException::withMessages([
                'material_source' => 'Upload a file or add an external link for this lesson type.',
            ]);
        }

        if ($validated['material_source'] === 'url' && ! filled($validated['media_url'] ?? null)) {
            throw ValidationException::withMessages([
                'media_url' => 'Enter the URL where learners can access this material.',
            ]);
        }

        if ($validated['material_source'] === 'upload') {
            if ($request->hasFile('material_file')) {
                $this->validateMaterialFileAgainstType($request->file('material_file'), $validated['lesson_type']);
            } elseif (! $lesson->media_disk_path && $validated['lesson_type'] !== Lesson::TYPE_TEXT) {
                throw ValidationException::withMessages([
                    'material_file' => 'Upload a file or switch to an external URL.',
                ]);
            }
        }

        [$nextUrl, $nextPath] = $this->materialPayloadFromRequest(
            $request,
            $tenant,
            $validated['material_source'],
            $validated['lesson_type'],
            $lesson,
        );

        if ($lesson->media_disk_path && $lesson->media_disk_path !== $nextPath) {
            Storage::disk('public')->delete($lesson->media_disk_path);
        }

        $lesson->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'lesson_type' => $validated['lesson_type'],
            'body' => $validated['body'] ?? null,
            'media_url' => $nextUrl,
            'media_disk_path' => $nextPath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);
        $lesson->save();

        return redirect()
            ->to($this->lessonsIndexUrl($tenant, $lesson->module_id, $lesson->course_id, $lesson->module_id ? 'module' : 'course'))
            ->with('status', 'Lesson updated.');
    }

    public function destroy(Tenant $tenant, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);
        $mid = $lesson->module_id;
        $cid = $lesson->course_id;
        $ctx = $lesson->module_id ? 'module' : 'course';
        $lesson->delete();

        return redirect()
            ->to($this->lessonsIndexUrl($tenant, $mid, $cid, $ctx))
            ->with('status', 'Lesson deleted.');
    }

    private function lessonsIndexUrl(Tenant $tenant, ?int $moduleId, int $courseId, string $context): string
    {
        if ($context === 'module' && $moduleId !== null) {
            return route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $moduleId]);
        }

        return route('coach.lessons.index', ['tenant' => $tenant, 'course_id' => $courseId]);
    }

    private function uniqueLessonSlugForCourse(int $courseId, string $base, ?int $ignoreLessonId): string
    {
        $slug = $base;
        $i = 1;
        while (Lesson::query()
            ->where('course_id', $courseId)
            ->where('slug', $slug)
            ->when($ignoreLessonId !== null, fn ($q) => $q->where('id', '!=', $ignoreLessonId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @return array{0: ?string, 1: ?string} [media_url, media_disk_path]
     */
    private function materialPayloadFromRequest(
        Request $request,
        Tenant $tenant,
        string $materialSource,
        string $lessonType,
        ?Lesson $existing,
    ): array {
        if ($materialSource === 'none') {
            return [null, null];
        }

        if ($materialSource === 'url') {
            $url = trim((string) $request->input('media_url', ''));

            return [$url !== '' ? $url : null, null];
        }

        if ($request->hasFile('material_file')) {
            $path = $request->file('material_file')->store("lessons/{$tenant->id}", 'public');

            return [null, $path];
        }

        if ($existing?->media_disk_path) {
            return [null, $existing->media_disk_path];
        }

        return [null, null];
    }

    private function validateMaterialFileAgainstType(UploadedFile $file, string $lessonType): void
    {
        $rules = match ($lessonType) {
            Lesson::TYPE_PDF => ['required', 'file', 'mimes:pdf', 'max:40960'],
            Lesson::TYPE_VIDEO => ['required', 'file', 'mimes:mp4,webm,mov,mpeg', 'max:524288'],
            Lesson::TYPE_AUDIO => ['required', 'file', 'mimes:mp3,m4a,wav,ogg,aac,mpeg', 'max:20480'],
            Lesson::TYPE_IMAGE => ['required', 'file', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            Lesson::TYPE_TEXT => ['required', 'file', 'mimes:jpeg,png,gif,webp,pdf', 'max:20480'],
            default => ['required', 'file', 'max:5120'],
        };

        Validator::make(
            ['material_file' => $file],
            ['material_file' => $rules]
        )->validate();
    }
}
