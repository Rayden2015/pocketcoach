<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

class EnrollmentService
{
    public function enrollFromProduct(User $user, Tenant $tenant, Product $product, string $source): Enrollment
    {
        if ($product->course_id !== null) {
            return Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'course_id' => $product->course_id,
                ],
                [
                    'program_id' => $product->course?->program_id,
                    'source' => $source,
                    'status' => 'active',
                ],
            );
        }

        if ($product->program_id !== null) {
            return Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'program_id' => $product->program_id,
                    'course_id' => null,
                ],
                [
                    'source' => $source,
                    'status' => 'active',
                ],
            );
        }

        throw new \InvalidArgumentException('Product must grant a course or program.');
    }
}
