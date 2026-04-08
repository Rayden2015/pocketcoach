<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyLearningWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_learning_requires_authentication(): void
    {
        $this->get('/my-learning')->assertRedirect(route('login'));
    }

    public function test_my_learning_lists_enrolled_courses_with_progress(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Program',
            'slug' => 'prog',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'My Course',
            'slug' => 'c',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'Mod',
            'slug' => 'm',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L1',
            'slug' => 'l1',
            'lesson_type' => 'text',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'program_id' => $program->id,
            'course_id' => null,
            'source' => 'free',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $this->get('/my-learning')
            ->assertOk()
            ->assertSee('My Course', false)
            ->assertSee('Space', false)
            ->assertSee('Continue learning', false);
    }

    public function test_my_learning_empty_when_not_enrolled(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/my-learning')
            ->assertOk()
            ->assertSee('do not have any enrollments', false);
    }

    public function test_my_learning_shows_explore_spaces_for_catalog_tenant_user_not_joined(): void
    {
        $other = Tenant::query()->create(['name' => 'Other Studio', 'slug' => 'other-studio']);
        Program::query()->create([
            'tenant_id' => $other->id,
            'title' => 'Live',
            'slug' => 'live',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        $this->actingAs(User::factory()->create());

        $this->get('/my-learning')
            ->assertOk()
            ->assertSee('Explore spaces you can join', false)
            ->assertSee('Other Studio', false)
            ->assertSee('Browse catalog', false);
    }
}
