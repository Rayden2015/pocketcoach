<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCoachTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_coach_actions_and_stats_for_staff(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Coach Space', 'slug' => 'cs', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $learner = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'program_id' => $program->id,
            'course_id' => null,
            'source' => 'test',
            'status' => 'active',
        ]);

        $this->actingAs($coach);
        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Programs &amp; courses', false);
        $response->assertSee('Daily reflections', false);
        $response->assertSee('Space you lead', false);
        $response->assertSee('Learners enrolled', false);
    }
}
