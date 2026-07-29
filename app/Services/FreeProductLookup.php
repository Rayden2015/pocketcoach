<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Str;

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

    public function courseHasOwnFreeOffer(Course $course): bool
    {
        return Product::query()
            ->where('tenant_id', $course->tenant_id)
            ->where('type', Product::TYPE_FREE)
            ->where('is_active', true)
            ->where('course_id', $course->id)
            ->exists();
    }

    /**
     * Create/activate or deactivate a course-specific free enrollment offer.
     */
    public function syncCourseFreeOffer(Tenant $tenant, Course $course, bool $allow): void
    {
        $existing = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', Product::TYPE_FREE)
            ->where('course_id', $course->id)
            ->get();

        if (! $allow) {
            foreach ($existing as $product) {
                $product->forceFill(['is_active' => false])->save();
            }

            return;
        }

        $active = $existing->first(fn (Product $p) => $p->is_active);
        if ($active !== null) {
            return;
        }

        $inactive = $existing->first();
        if ($inactive !== null) {
            $inactive->forceFill(['is_active' => true, 'name' => 'Free — '.$course->title])->save();

            return;
        }

        $baseSlug = 'free-'.($course->slug ?: 'course-'.$course->id);
        $slug = $baseSlug;
        $i = 1;
        while (Product::query()->where('tenant_id', $tenant->id)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i;
            $i++;
        }

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Free — '.$course->title,
            'slug' => Str::limit($slug, 240, ''),
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'program_id' => null,
            'is_active' => true,
        ]);
    }
}
