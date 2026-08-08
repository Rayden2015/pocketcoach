<?php

namespace Tests\Feature;

use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerReflectionDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, learner: User, coach: User, prompt: ReflectionPrompt}
     */
    private function seedPublishedReflection(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Reflect Space',
            'slug' => 'reflect-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Morning check-in',
            'body' => 'What are you grateful for today?',
            'is_published' => true,
            'published_at' => now(),
        ]);

        return compact('tenant', 'learner', 'coach', 'prompt');
    }

    public function test_learn_dashboard_shows_todays_reflection(): void
    {
        $s = $this->seedPublishedReflection();

        $this->actingAs($s['learner'])
            ->get(route('learn.dashboard', $s['tenant']))
            ->assertOk()
            ->assertSee('Today', false)
            ->assertSee('Morning check-in', false)
            ->assertSee('What are you grateful for today?', false)
            ->assertSee('Open reflection', false);
    }

    public function test_learn_dashboard_shows_view_link_when_learner_already_responded(): void
    {
        $s = $this->seedPublishedReflection();
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $s['prompt']->id,
            'user_id' => $s['learner']->id,
            'body' => 'Family and health.',
            'first_submitted_at' => now(),
        ]);

        $this->actingAs($s['learner'])
            ->get(route('learn.dashboard', $s['tenant']))
            ->assertOk()
            ->assertSee('View your reflection', false)
            ->assertDontSee('>New<', false);
    }

    public function test_learn_catalog_shows_todays_reflection(): void
    {
        $s = $this->seedPublishedReflection();

        $this->actingAs($s['learner'])
            ->get(route('learn.catalog', $s['tenant']))
            ->assertOk()
            ->assertSee('Morning check-in', false)
            ->assertSee('Open reflection', false);
    }

    public function test_public_catalog_shows_todays_reflection_for_guests(): void
    {
        $s = $this->seedPublishedReflection();

        $this->get(route('public.catalog', $s['tenant']))
            ->assertOk()
            ->assertSee('Morning check-in', false)
            ->assertSee('Log in to respond', false);
    }

    public function test_latest_reflection_api_includes_my_response_when_authenticated(): void
    {
        $s = $this->seedPublishedReflection();
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $s['prompt']->id,
            'user_id' => $s['learner']->id,
            'body' => 'Done.',
            'first_submitted_at' => now(),
        ]);

        $token = $s['learner']->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/tenants/{$s['tenant']->slug}/reflection-prompts/latest")
            ->assertOk()
            ->assertJsonPath('data.id', $s['prompt']->id)
            ->assertJsonPath('data.my_response.body', 'Done.');
    }
}
