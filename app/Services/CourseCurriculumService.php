<?php

namespace App\Services;

use App\Models\Course;
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
     * @return Collection<int, \App\Models\Lesson>
     */
    public static function flattenedPublishedLessons(Course $course): Collection
    {
        $root = $course->relationLoaded('rootLessons')
            ? $course->rootLessons
            : $course->rootLessons()->where('is_published', true)->orderBy('sort_order')->get();

        $fromModules = $course->relationLoaded('modules')
            ? $course->modules->flatMap(fn ($m) => $m->relationLoaded('lessons') ? $m->lessons : collect())
            : collect();

        return $root->concat($fromModules)->values();
    }
}
