<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSearchWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_authentication(): void
    {
        $this->get('/search')->assertRedirect(route('login'));
    }

    public function test_search_finds_course_in_user_space(): void
    {
        $tenant = Tenant::query()->create(['name' => 'S', 'slug' => 's']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Advanced Baking Workshop',
            'slug' => 'bake',
            'summary' => 'Cookies and more',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        $this->actingAs($user);

        $this->get('/search?q=baking')
            ->assertOk()
            ->assertSee('Advanced Baking Workshop', false)
            ->assertSee('S', false);
    }

    public function test_search_does_not_leak_other_tenant_courses(): void
    {
        $t1 = Tenant::query()->create(['name' => 'A', 'slug' => 'a']);
        $t2 = Tenant::query()->create(['name' => 'B', 'slug' => 'b']);
        $p1 = Program::query()->create([
            'tenant_id' => $t1->id, 'title' => 'P1', 'slug' => 'p1', 'sort_order' => 0, 'is_published' => true,
        ]);
        $p2 = Program::query()->create([
            'tenant_id' => $t2->id, 'title' => 'P2', 'slug' => 'p2', 'sort_order' => 0, 'is_published' => true,
        ]);
        Course::query()->create([
            'tenant_id' => $t1->id, 'program_id' => $p1->id, 'title' => 'Secret Alpha Course', 'slug' => 'x', 'sort_order' => 0, 'is_published' => true,
        ]);
        Course::query()->create([
            'tenant_id' => $t2->id, 'program_id' => $p2->id, 'title' => 'Secret Beta Course', 'slug' => 'y', 'sort_order' => 0, 'is_published' => true,
        ]);

        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $t1->id,
            'user_id' => $user->id,
            'program_id' => $p1->id,
            'course_id' => null,
            'source' => 'free',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $this->get('/search?q=Secret')
            ->assertOk()
            ->assertSee('Secret Alpha Course', false)
            ->assertDontSee('Secret Beta Course', false);
    }
}
