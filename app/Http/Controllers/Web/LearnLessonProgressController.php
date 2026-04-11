<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearnLessonProgressController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function update(Request $request, Tenant $tenant, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $lesson->load(['module.course', 'course']);
        $course = $lesson->module?->course ?? $lesson->course;
        abort_unless($course !== null && $course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse($request->user(), $course)) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:65535'],
            'notes_is_public' => ['nullable', 'boolean'],
            'mark_complete' => ['nullable', 'in:0,1'],
        ]);

        $attributes = [
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ];

        $notes = $validated['notes'] ?? null;
        $notesIsPublic = $request->boolean('notes_is_public');
        if ($notes === null || trim((string) $notes) === '') {
            $notesIsPublic = false;
        }

        $values = [
            'notes' => $notes,
            'notes_is_public' => $notesIsPublic,
            'completed_at' => ($validated['mark_complete'] ?? '0') === '1' ? now() : null,
        ];

        LessonProgress::query()->updateOrCreate($attributes, $values);

        return redirect()
            ->route('learn.lesson', [$tenant, $lesson])
            ->with('status', 'Saved your progress.');
    }
}
