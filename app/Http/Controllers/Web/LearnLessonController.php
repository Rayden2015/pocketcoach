<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LearnLessonController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function show(Tenant $tenant, Lesson $lesson): View|RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        $lesson->load('module.course');
        $course = $lesson->module->course ?? abort(404);
        abort_unless($course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
            return redirect()
                ->route('learn.course', [$tenant, $course])
                ->with('warning', 'Enroll in this course on the page below to open lessons.');
        }

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
        ]);

        $flat = $course->modules->flatMap(fn ($m) => $m->lessons);
        $flatIds = $flat->pluck('id');
        $index = $flat->search(fn ($l) => $l->is($lesson));
        $prevLesson = is_int($index) && $index > 0 ? $flat[$index - 1] : null;
        $nextLesson = is_int($index) && $index < $flat->count() - 1 ? $flat[$index + 1] : null;

        $completedLessonIds = LessonProgress::query()
            ->where('user_id', auth()->id())
            ->whereIn('lesson_id', $flatIds)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');

        $progress = LessonProgress::query()
            ->where('user_id', auth()->id())
            ->where('lesson_id', $lesson->id)
            ->first();

        return view('learn.lesson', [
            'tenant' => $tenant,
            'course' => $course,
            'lesson' => $lesson,
            'progress' => $progress,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'completedLessonIds' => $completedLessonIds,
        ]);
    }
}
