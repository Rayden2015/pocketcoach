<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class CourseAccessService
{
    public function canAccessCourse(User $user, Course $course): bool
    {
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
    public function accessibleCourseIdsForUserInTenant(User $user, int $tenantId): array
    {
        $enrollments = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get(['course_id', 'program_id']);

        $ids = collect();
        foreach ($enrollments as $enrollment) {
            if ($enrollment->course_id !== null) {
                $ids->push((int) $enrollment->course_id);
            }
            if ($enrollment->program_id !== null) {
                $ids = $ids->merge(
                    Course::query()->where('program_id', $enrollment->program_id)->pluck('id'),
                );
            }
        }

        return $ids->unique()->values()->all();
    }
}
