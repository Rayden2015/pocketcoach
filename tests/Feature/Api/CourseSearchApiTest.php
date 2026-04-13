<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_empty_when_query_too_short(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search/courses?q=a')
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_search_finds_course_by_title_in_member_tenant(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
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
            'title' => 'Unique Alpha Course',
            'slug' => 'uac',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search/courses?q=Alpha')
            ->assertOk()
            ->assertJsonPath('data.0.id', $course->id)
            ->assertJsonPath('data.0.tenant_slug', 'sp')
            ->assertJsonPath('data.0.title', 'Unique Alpha Course');
    }
}
