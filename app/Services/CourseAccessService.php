<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class CourseAccessService
{
    public function canAccessCourse(?User $user, Course $course): bool
    {
        if ($user === null) {
            return false;
        }

        if (Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists()) {
            return true;
        }

        if ($course->program_id === null) {
            return false;
        }

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('program_id', $course->program_id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function accessibleCourseIdsForUserInTenant(?User $user, int $tenantId): array
    {
        if ($user === null) {
            return [];
        }

        $enrollments = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get(['course_id', 'program_id']);

        $ids = collect();
        $programIds = collect();

        foreach ($enrollments as $enrollment) {
            if ($enrollment->course_id !== null) {
                $ids->push((int) $enrollment->course_id);
            }
            if ($enrollment->program_id !== null) {
                $programIds->push((int) $enrollment->program_id);
            }
        }

        if ($programIds->isNotEmpty()) {
            $ids = $ids->merge(
                Course::query()
                    ->whereIn('program_id', $programIds->unique()->values())
                    ->pluck('id'),
            );
        }

        return $ids->unique()->values()->all();
    }
}
