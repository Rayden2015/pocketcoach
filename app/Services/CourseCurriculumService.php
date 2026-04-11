<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Collection;

final class CourseCurriculumService
{
    /**
     * @return array<string, \Closure>
     */
    public static function eagerLoadPublishedCurriculum(): array
    {
        return [
            'rootLessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
        ];
    }

    /**
     * Course-level lessons first, then each module’s lessons (published only when relations loaded with filters).
     *
     * @return Collection<int, Lesson>
     */
    public static function flattenedPublishedLessons(Course $course): Collection
    {
        $root = $course->relationLoaded('rootLessons')
            ? $course->rootLessons
            : $course->rootLessons()->where('is_published', true)->orderBy('sort_order')->get();

        if ($course->relationLoaded('modules')) {
            $fromModules = $course->modules->flatMap(function ($m) {
                if ($m->relationLoaded('lessons')) {
                    return $m->lessons;
                }

                return $m->lessons()->where('is_published', true)->orderBy('sort_order')->get();
            });
        } else {
            $fromModules = $course->modules()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->with([
                    'lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
                ])
                ->get()
                ->flatMap(fn ($m) => $m->lessons);
        }

        return $root->concat($fromModules)->values();
    }

    /**
     * Published lesson ids for a course: root lessons (no module) plus lessons under published modules.
     *
     * @return Collection<int, int>
     */
    public static function publishedLessonIdsForCourse(int $courseId): Collection
    {
        return Lesson::query()
            ->where('course_id', $courseId)
            ->where('is_published', true)
            ->where(function ($q): void {
                $q->whereNull('module_id')
                    ->orWhereHas('module', fn ($m) => $m->where('is_published', true));
            })
            ->pluck('id');
    }
}
