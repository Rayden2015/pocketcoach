<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, program: Program, course: Course, module: Module, lesson: Lesson}
     */
    private function seedTenantWithPublishedCourse(): array
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
            'course_id' => $course->id,
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

    public function test_course_page_shows_enroll_when_free_product_exists(): void
    {
        $data = $this->seedTenantWithPublishedCourse();
        Product::query()->create([
            'tenant_id' => $data['tenant']->id,
            'name' => 'Freebie',
            'slug' => 'freebie',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $data['course']->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get("/{$data['tenant']->slug}/learn/courses/{$data['course']->id}")
            ->assertOk()
            ->assertSee('Enroll free', false)
            ->assertDontSee('Self-enrollment is not enabled', false);
    }

    public function test_coach_can_enable_free_enrollment_from_course_edit(): void
    {
        $data = $this->seedTenantWithPublishedCourse();
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $this->actingAs($coach)
            ->put(route('coach.courses.update', [$data['tenant'], $data['course']]), [
                'program_id' => $data['program']->id,
                'title' => $data['course']->title,
                'slug' => $data['course']->slug,
                'summary' => $data['course']->summary,
                'sort_order' => $data['course']->sort_order,
                'is_published' => '1',
                'is_featured' => '0',
                'allow_free_enrollment' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'tenant_id' => $data['tenant']->id,
            'course_id' => $data['course']->id,
            'type' => Product::TYPE_FREE,
            'is_active' => true,
        ]);

        $learner = User::factory()->create();
        $this->actingAs($learner)
            ->get(route('learn.course', [$data['tenant'], $data['course']]))
            ->assertOk()
            ->assertSee('Enroll free', false);

        $this->actingAs($learner)
            ->post(route('learn.course.enroll', [$data['tenant'], $data['course']]))
            ->assertRedirect(route('learn.course', [$data['tenant'], $data['course']]));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $learner->id,
            'course_id' => $data['course']->id,
            'status' => 'active',
        ]);
    }

    public function test_learner_can_post_free_enroll_and_open_lesson(): void
    {
        $data = $this->seedTenantWithPublishedCourse();
        Product::query()->create([
            'tenant_id' => $data['tenant']->id,
            'name' => 'Freebie',
            'slug' => 'freebie',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $data['course']->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post("/{$data['tenant']->slug}/learn/courses/{$data['course']->id}/enroll")
            ->assertRedirect(route('learn.course', [$data['tenant'], $data['course']]));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'tenant_id' => $data['tenant']->id,
            'course_id' => $data['course']->id,
            'status' => 'active',
        ]);

        $this->get("/{$data['tenant']->slug}/learn/lessons/{$data['lesson']->id}")
            ->assertOk()
            ->assertSee($data['lesson']->title, false);
    }

    public function test_lesson_redirects_to_course_when_not_enrolled(): void
    {
        $data = $this->seedTenantWithPublishedCourse();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get("/{$data['tenant']->slug}/learn/lessons/{$data['lesson']->id}")
            ->assertRedirect(route('learn.course', [$data['tenant'], $data['course']]));
    }

    public function test_authenticated_user_can_join_space_via_web(): void
    {
        $data = $this->seedTenantWithPublishedCourse();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post("/{$data['tenant']->slug}/join")
            ->assertRedirect(route('learn.catalog', $data['tenant']));

        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
    }

    public function test_join_space_is_idempotent_for_web(): void
    {
        $data = $this->seedTenantWithPublishedCourse();
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
        $this->actingAs($user);

        $this->post("/{$data['tenant']->slug}/join")
            ->assertRedirect(route('learn.catalog', $data['tenant']));

        $this->assertSame(1, TenantMembership::query()
            ->where('tenant_id', $data['tenant']->id)
            ->where('user_id', $user->id)
            ->count());
    }
}
