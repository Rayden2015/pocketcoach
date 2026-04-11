<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class MyLearningOverviewService
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    /**
     * All published courses the user can access, with progress — across every space they are enrolled in.
     *
     * @return Collection<int, array{
     *     tenant: Tenant,
     *     course: Course,
     *     lessons_total: int,
     *     lessons_completed: int,
     *     percent: int,
     *     continue_url: string,
     *     is_complete: bool
     * }>
     */
    public function coursesForUser(User $user): Collection
    {
        $tenantIds = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->distinct()
            ->pluck('tenant_id');

        $rows = collect();

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::query()->find($tenantId);
            if (! $tenant || ($tenant->status !== null && ! $tenant->isActive())) {
                continue;
            }

            $courseIds = $this->access->accessibleCourseIdsForUserInTenant($user, (int) $tenantId);
            if ($courseIds === []) {
                continue;
            }

            $courses = Course::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $courseIds)
                ->where('is_published', true)
                ->with('program')
                ->orderBy('title')
                ->get();

            foreach ($courses as $course) {
                $lessonIds = $this->publishedLessonIdsForCourse($course->id);
                $total = $lessonIds->count();
                $completed = $total === 0 ? 0 : LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->where('tenant_id', $tenantId)
                    ->whereIn('lesson_id', $lessonIds)
                    ->whereNotNull('completed_at')
                    ->count();

                $percent = $total > 0 ? (int) round(100 * $completed / $total) : 0;
                $isComplete = $total > 0 && $completed >= $total;

                $rows->push([
                    'tenant' => $tenant,
                    'course' => $course,
                    'lessons_total' => $total,
                    'lessons_completed' => $completed,
                    'percent' => $percent,
                    'continue_url' => $this->continueUrl($user, $tenant, $course),
                    'is_complete' => $isComplete,
                ]);
            }
        }

        return $rows
            ->sort(function (array $a, array $b): int {
                if ($a['is_complete'] !== $b['is_complete']) {
                    return $a['is_complete'] <=> $b['is_complete'];
                }
                if ($a['percent'] !== $b['percent']) {
                    return $b['percent'] <=> $a['percent'];
                }

                return strcmp($a['course']->title, $b['course']->title);
            })
            ->values();
    }

    private function publishedLessonIdsForCourse(int $courseId): Collection
    {
        return CourseCurriculumService::publishedLessonIdsForCourse($courseId);
    }

    private function continueUrl(User $user, Tenant $tenant, Course $course): string
    {
        $course->loadMissing(CourseCurriculumService::eagerLoadPublishedCurriculum());

        foreach (CourseCurriculumService::flattenedPublishedLessons($course) as $lesson) {
            $p = LessonProgress::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if ($p === null || $p->completed_at === null) {
                return route('learn.lesson', [$tenant, $lesson]);
            }
        }

        return route('learn.course', [$tenant, $course]);
    }
}
