<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminLessonsApiTest extends TestCase
{
    use RefreshDatabase;

    private function staffUserForTenant(Tenant $tenant): User
    {
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        return $user;
    }

    public function test_update_rejects_module_from_another_course(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $courseA = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'CA',
            'slug' => 'ca',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $courseB = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'CB',
            'slug' => 'cb',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $moduleA = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $courseA->id,
            'title' => 'MA',
            'slug' => 'ma',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $moduleB = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $courseB->id,
            'title' => 'MB',
            'slug' => 'mb',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $courseB->id,
            'module_id' => $moduleB->id,
            'title' => 'Other',
            'slug' => 'shared-slug',
            'lesson_type' => 'text',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        $lessonA = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $courseA->id,
            'module_id' => $moduleA->id,
            'title' => 'L',
            'slug' => 'shared-slug',
            'lesson_type' => 'text',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        Sanctum::actingAs($this->staffUserForTenant($tenant));

        $this->putJson("/api/v1/tenants/{$tenant->slug}/admin/lessons/{$lessonA->id}", [
            'module_id' => $moduleB->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['module_id']);

        $lessonA->refresh();
        $this->assertSame($moduleA->id, $lessonA->module_id);
        $this->assertSame($courseA->id, $lessonA->course_id);
    }

    public function test_update_allows_moving_lesson_between_modules_in_same_course(): void
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

        $module1 = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M1',
            'slug' => 'm1',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $module2 = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M2',
            'slug' => 'm2',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module1->id,
            'title' => 'L',
            'slug' => 'lesson-slug',
            'lesson_type' => 'text',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        Sanctum::actingAs($this->staffUserForTenant($tenant));

        $this->putJson("/api/v1/tenants/{$tenant->slug}/admin/lessons/{$lesson->id}", [
            'module_id' => $module2->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.module_id', $module2->id);

        $lesson->refresh();
        $this->assertSame($module2->id, $lesson->module_id);
        $this->assertSame($course->id, $lesson->course_id);
    }
}
