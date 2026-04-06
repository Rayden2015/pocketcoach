<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Product;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, program: Program, course: Course, product: Product}
     */
    private function tenantWithFreeProductForCourse(): array
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Freebie',
            'slug' => 'freebie',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'is_active' => true,
        ]);

        return compact('tenant', 'program', 'course', 'product');
    }

    public function test_free_enroll_creates_enrollment(): void
    {
        $data = $this->tenantWithFreeProductForCourse();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/enrollments/free", [
            'product_id' => $data['product']->id,
        ])->assertOk()->assertJsonPath('enrollment.course_id', $data['course']->id);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'tenant_id' => $data['tenant']->id,
            'course_id' => $data['course']->id,
            'status' => 'active',
        ]);
    }

    public function test_free_enroll_rejects_one_time_product(): void
    {
        $data = $this->tenantWithFreeProductForCourse();
        $paid = Product::query()->create([
            'tenant_id' => $data['tenant']->id,
            'name' => 'Paid',
            'slug' => 'paid',
            'type' => Product::TYPE_ONE_TIME,
            'amount_minor' => 1000,
            'currency' => 'NGN',
            'course_id' => $data['course']->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/enrollments/free", [
            'product_id' => $paid->id,
        ])->assertStatus(422)->assertJsonPath('message', 'Product is not a free offer.');
    }

    public function test_free_enroll_404_for_product_in_other_tenant(): void
    {
        $data = $this->tenantWithFreeProductForCourse();
        $otherTenant = Tenant::query()->create(['name' => 'O', 'slug' => 'o']);
        $otherProgram = Program::query()->create([
            'tenant_id' => $otherTenant->id,
            'title' => 'OP',
            'slug' => 'op',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $otherCourse = Course::query()->create([
            'tenant_id' => $otherTenant->id,
            'program_id' => $otherProgram->id,
            'title' => 'OC',
            'slug' => 'oc',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $orphanProduct = Product::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other free',
            'slug' => 'other-free',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $otherCourse->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/enrollments/free", [
            'product_id' => $orphanProduct->id,
        ])->assertNotFound();
    }

    public function test_free_enroll_is_idempotent(): void
    {
        $data = $this->tenantWithFreeProductForCourse();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/enrollments/free", [
            'product_id' => $data['product']->id,
        ])->assertOk();

        $count = Enrollment::query()->where('user_id', $user->id)->where('course_id', $data['course']->id)->count();
        $this->assertSame(1, $count);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/enrollments/free", [
            'product_id' => $data['product']->id,
        ])->assertOk();

        $this->assertSame(1, Enrollment::query()->where('user_id', $user->id)->where('course_id', $data['course']->id)->count());
    }
}
