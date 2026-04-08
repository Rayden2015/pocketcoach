<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Product;
use App\Models\Tenant;

class FreeProductLookup
{
    /**
     * Active free product for this course: prefers a course-specific offer, then a program-wide offer (course_id null).
     */
    public function productIdForCourse(Tenant $tenant, Course $course): ?int
    {
        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', Product::TYPE_FREE)
            ->where('is_active', true)
            ->where(function ($q) use ($course): void {
                $q->where('course_id', $course->id);
                if ($course->program_id !== null) {
                    $q->orWhere('program_id', $course->program_id);
                }
            })
            ->get(['id', 'course_id', 'program_id']);

        $courseSpecific = $products->first(fn (Product $p) => $p->course_id === $course->id);
        if ($courseSpecific !== null) {
            return $courseSpecific->id;
        }

        $programWide = $products->first(fn (Product $p) => $p->program_id === $course->program_id && $p->course_id === null);

        return $programWide?->id;
    }
}
