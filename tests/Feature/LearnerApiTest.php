<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Product;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearnerApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedTenantWithCourse(): array
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
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'title' => 'L1',
            'slug' => 'l1',
            'lesson_type' => 'text',
            'body' => 'Hello',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        return compact('tenant', 'program', 'course', 'module', 'lesson');
    }

    public function test_catalog_requires_authentication(): void
    {
        $data = $this->seedTenantWithCourse();
        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/catalog")->assertUnauthorized();
    }

    public function test_catalog_lists_published_programs(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/catalog");
        $response->assertOk()
            ->assertJsonPath('data.0.slug', 'p')
            ->assertJsonPath('data.0.courses.0.is_enrolled', false)
            ->assertJsonPath('data.0.courses.0.free_product_id', null);
    }

    public function test_course_returns_403_without_enrollment(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}")
            ->assertForbidden()
            ->assertJsonPath('free_product_id', null);
    }

    public function test_course_403_includes_free_product_id_when_available(): void
    {
        $data = $this->seedTenantWithCourse();
        $product = Product::query()->create([
            'tenant_id' => $data['tenant']->id,
            'name' => 'Freebie',
            'slug' => 'freebie',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $data['course']->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}")
            ->assertForbidden()
            ->assertJsonPath('free_product_id', $product->id);
    }

    public function test_course_returns_content_when_enrolled(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}");
        $response->assertOk()
            ->assertJsonPath('data.modules.0.lessons.0.body', 'Hello')
            ->assertJsonPath('data.modules.0.lessons.0.progress', null);
    }

    public function test_course_includes_lesson_progress_for_learner(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        LessonProgress::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'lesson_id' => $data['lesson']->id,
            'notes' => 'Feedback here',
            'position_seconds' => 0,
            'completed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}");
        $response->assertOk()
            ->assertJsonPath('data.modules.0.lessons.0.progress.notes', 'Feedback here');
        $this->assertNotNull($response->json('data.modules.0.lessons.0.progress.completed_at'));
    }

    public function test_learning_summary_counts_completed_lessons(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        LessonProgress::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'lesson_id' => $data['lesson']->id,
            'completed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/learning-summary")
            ->assertOk()
            ->assertJsonPath('data.0.lessons_total', 1)
            ->assertJsonPath('data.0.lessons_completed', 1);
    }

    public function test_join_creates_learner_membership(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/join")
            ->assertCreated()
            ->assertJsonPath('role', 'learner');

        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/join")
            ->assertOk()
            ->assertJsonPath('message', 'Already a member of this space.');
    }

    public function test_continue_returns_first_incomplete_lesson(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/continue")
            ->assertOk()
            ->assertJsonPath('data.lesson.slug', 'l1');
    }

    public function test_lesson_progress_updates(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'completed' => true,
            'notes' => 'Great',
        ])->assertOk();

        $this->assertDatabaseHas('lesson_progress', [
            'lesson_id' => $data['lesson']->id,
            'user_id' => $user->id,
            'notes' => 'Great',
        ]);

        $progress = LessonProgress::query()->where('lesson_id', $data['lesson']->id)->where('user_id', $user->id)->first();
        $this->assertNotNull($progress->completed_at);
    }

    public function test_learner_cannot_access_admin_programs(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/admin/programs")->assertForbidden();
    }

    public function test_staff_can_list_admin_programs(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/admin/programs")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_staff_can_create_course_under_program(): void
    {
        $data = $this->seedTenantWithCourse();
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$data['tenant']->slug}/admin/courses", [
            'program_id' => $data['program']->id,
            'title' => 'New course',
            'is_published' => false,
        ])->assertCreated()->assertJsonPath('data.title', 'New course');
    }
}
