<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomeDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_dashboard_returns_learner_payload_and_null_coach_for_learner(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'sp',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/home-dashboard")
            ->assertOk()
            ->assertJsonPath('data.membership.role', 'learner')
            ->assertJsonPath('data.membership.is_staff', false)
            ->assertJsonPath('data.coach', null)
            ->assertJsonStructure([
                'data' => [
                    'learner' => [
                        'lessons_completed_7d',
                        'lessons_completed_30d',
                        'courses_enrolled',
                        'courses_completed',
                        'courses_in_progress',
                        'courses_not_started',
                        'continue',
                    ],
                ],
            ]);
    }

    public function test_home_dashboard_includes_coach_section_for_staff(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Coach Space',
            'slug' => 'cs',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/home-dashboard")
            ->assertOk()
            ->assertJsonPath('data.membership.is_staff', true)
            ->assertJsonStructure([
                'data' => [
                    'coach' => [
                        'programs_live',
                        'programs_draft',
                        'courses_live',
                        'active_enrollments',
                        'learners_with_enrollment',
                        'learner_members',
                        'lesson_completions_7d',
                        'reflection_prompts_live',
                        'scheduled_reflections_pending',
                    ],
                ],
            ]);
    }
}
