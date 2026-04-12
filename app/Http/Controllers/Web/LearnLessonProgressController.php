<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\CourseCurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearnLessonProgressController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function update(Request $request, Tenant $tenant, Lesson $lesson): RedirectResponse|JsonResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $lesson->load(['module.course', 'course']);
        $course = $lesson->module?->course ?? $lesson->course;
        abort_unless($course !== null && $course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse($request->user(), $course)) {
            abort(403);
        }

        $attributes = [
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ];

        $existing = LessonProgress::query()->where($attributes)->first();

        $progressOnly = $request->ajax()
            && ! $request->filled('intent')
            && ($request->has('content_progress_percent') || $request->has('position_seconds'));

        if ($progressOnly) {
            $validated = $request->validate([
                'content_progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
                'position_seconds' => ['nullable', 'integer', 'min:0'],
            ]);

            $values = [];
            if ($request->has('content_progress_percent')) {
                $incoming = (int) ($validated['content_progress_percent'] ?? 0);
                $prev = (int) ($existing?->content_progress_percent ?? 0);
                $values['content_progress_percent'] = max($prev, $incoming);
            }
            if ($request->has('position_seconds')) {
                $incoming = (int) ($validated['position_seconds'] ?? 0);
                $prev = (int) ($existing?->position_seconds ?? 0);
                $values['position_seconds'] = max($prev, $incoming);
            }

            if ($values === []) {
                return response()->json(['message' => 'Nothing to update'], 422);
            }

            LessonProgress::query()->updateOrCreate($attributes, $values);

            return response()->json(['ok' => true]);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:65535'],
            'notes_is_public' => ['nullable', 'boolean'],
            'intent' => ['required', 'in:save_notes,complete,incomplete,next'],
            'content_progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'position_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $course->load(CourseCurriculumService::eagerLoadPublishedCurriculum());
        $flat = CourseCurriculumService::flattenedPublishedLessons($course);
        $index = $flat->search(fn ($l) => $l->is($lesson));
        $nextLesson = is_int($index) && $index < $flat->count() - 1 ? $flat[$index + 1] : null;

        $notes = $validated['notes'] ?? null;
        $notesIsPublic = $request->boolean('notes_is_public');
        if ($notes === null || trim((string) $notes) === '') {
            $notesIsPublic = false;
        }

        $intent = $validated['intent'];

        $values = [
            'notes' => $notes,
            'notes_is_public' => $notesIsPublic,
        ];

        if ($request->filled('content_progress_percent')) {
            $incoming = (int) $validated['content_progress_percent'];
            $prev = (int) ($existing?->content_progress_percent ?? 0);
            $values['content_progress_percent'] = max($prev, $incoming);
        }
        if ($request->filled('position_seconds')) {
            $incoming = (int) $validated['position_seconds'];
            $prev = (int) ($existing?->position_seconds ?? 0);
            $values['position_seconds'] = max($prev, $incoming);
        }

        if ($intent === 'save_notes') {
            LessonProgress::query()->updateOrCreate($attributes, $values);

            return redirect()
                ->route('learn.lesson', [$tenant, $lesson])
                ->with('status', 'Saved your notes.');
        }

        if ($intent === 'complete' || $intent === 'next') {
            $values['completed_at'] = now();
            $fromForm = (int) ($values['content_progress_percent'] ?? $existing?->content_progress_percent ?? 0);
            $values['content_progress_percent'] = max(100, $fromForm);
        } elseif ($intent === 'incomplete') {
            $values['completed_at'] = null;
        }

        LessonProgress::query()->updateOrCreate($attributes, $values);

        if ($intent === 'next' && $nextLesson !== null) {
            return redirect()
                ->route('learn.lesson', [$tenant, $nextLesson])
                ->with('status', 'Lesson completed. On to the next one.');
        }

        $message = match ($intent) {
            'complete' => 'Marked complete.',
            'incomplete' => 'Marked incomplete.',
            'next' => 'Marked complete.',
            default => 'Saved.',
        };

        return redirect()
            ->route('learn.lesson', [$tenant, $lesson])
            ->with('status', $message);
    }
}
