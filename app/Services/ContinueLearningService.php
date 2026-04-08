<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class ContinueLearningService
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    /**
     * @return array{course: Course, lesson: Lesson, progress: ?LessonProgress}|null
     */
    public function nextForUserInTenant(User $user, int $tenantId): ?array
    {
        $courseIds = $this->access->accessibleCourseIdsForUserInTenant($user, $tenantId);
        if ($courseIds === []) {
            return null;
        }

        $courses = Course::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $courseIds)
            ->where('is_published', true)
            ->with(CourseCurriculumService::eagerLoadPublishedCurriculum())
            ->orderBy('id')
            ->get();

        foreach ($courses as $course) {
            $flat = $this->flattenLessons($course);
            foreach ($flat as $lesson) {
                $progress = LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();

                if ($progress === null || $progress->completed_at === null) {
                    return [
                        'course' => $course,
                        'lesson' => $lesson,
                        'progress' => $progress,
                    ];
                }
            }
        }

        $lastCourse = $courses->first();
        if ($lastCourse === null) {
            return null;
        }

        $flat = $this->flattenLessons($lastCourse);
        $lastLesson = $flat->last();

        if ($lastLesson === null) {
            return null;
        }

        return [
            'course' => $lastCourse,
            'lesson' => $lastLesson,
            'progress' => LessonProgress::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lastLesson->id)
                ->first(),
        ];
    }

    /**
     * @return Collection<int, Lesson>
     */
    private function flattenLessons(Course $course): Collection
    {
        return CourseCurriculumService::flattenedPublishedLessons($course);
    }
}
