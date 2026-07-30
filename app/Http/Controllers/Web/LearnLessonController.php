<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use App\Services\CourseCurriculumService;
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

        $context = $this->lessonContext($tenant, $lesson);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('learn.lesson', $context);
    }

    public function studio(Tenant $tenant, Lesson $lesson): View|RedirectResponse
    {
        abort_unless($lesson->tenant_id === $tenant->id, 404);

        if (! $lesson->supportsMediaStudio()) {
            return redirect()
                ->route('learn.lesson', [$tenant, $lesson])
                ->with('warning', 'Studio view is available for image, video, audio, and PDF lessons with media.');
        }

        $context = $this->lessonContext($tenant, $lesson);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('learn.lesson-studio', $context);
    }

    /**
     * @return array{
     *     tenant: Tenant,
     *     course: \App\Models\Course,
     *     lesson: Lesson,
     *     progress: ?LessonProgress,
     *     publicPeerNotes: \Illuminate\Support\Collection<int, LessonProgress>,
     *     prevLesson: ?Lesson,
     *     nextLesson: ?Lesson,
     *     completedLessonIds: \Illuminate\Support\Collection<int, int>
     * }|RedirectResponse
     */
    private function lessonContext(Tenant $tenant, Lesson $lesson): array|RedirectResponse
    {
        $lesson->load(['module.course', 'course']);
        $course = $lesson->module?->course ?? $lesson->course;
        abort_unless($course !== null && $course->tenant_id === $tenant->id, 404);

        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
            return redirect()
                ->route('learn.course', [$tenant, $course])
                ->with('warning', 'Enroll in this course on the page below to open lessons.');
        }

        $course->load(CourseCurriculumService::eagerLoadPublishedCurriculum());

        $flat = CourseCurriculumService::flattenedPublishedLessons($course);
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
            ->withCount('conversationMessages')
            ->first();

        $publicPeerNotes = LessonProgress::query()
            ->where('lesson_id', $lesson->id)
            ->where('tenant_id', $tenant->id)
            ->where('notes_is_public', true)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->where('user_id', '!=', auth()->id())
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return [
            'tenant' => $tenant,
            'course' => $course,
            'lesson' => $lesson,
            'progress' => $progress,
            'publicPeerNotes' => $publicPeerNotes,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'completedLessonIds' => $completedLessonIds,
        ];
    }
}
