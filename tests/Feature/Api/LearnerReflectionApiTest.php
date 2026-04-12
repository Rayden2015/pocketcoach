<?php

namespace Tests\Feature\Api;

use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearnerReflectionApiTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithReflectionsEnabled(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'R',
            'slug' => 'r',
            'status' => Tenant::STATUS_ACTIVE,
            'settings' => [
                'reflections' => [
                    'enabled' => true,
                    'notify_email' => false,
                    'notify_database' => true,
                ],
            ],
        ]);
    }

    public function test_latest_returns_null_when_no_prompt(): void
    {
        $tenant = $this->tenantWithReflectionsEnabled();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/latest")
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_latest_returns_most_recent_published_prompt(): void
    {
        $tenant = $this->tenantWithReflectionsEnabled();
        ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'Old',
            'body' => 'Old body',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        $new = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'Newest',
            'body' => 'New body',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/latest")
            ->assertOk()
            ->assertJsonPath('data.id', $new->id)
            ->assertJsonPath('data.title', 'Newest');
    }

    public function test_latest_returns_404_when_reflections_disabled(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Off',
            'slug' => 'off',
            'status' => Tenant::STATUS_ACTIVE,
            'settings' => [
                'reflections' => [
                    'enabled' => false,
                ],
            ],
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/latest")
            ->assertNotFound();
    }

    public function test_show_includes_my_response_when_present(): void
    {
        $tenant = $this->tenantWithReflectionsEnabled();
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'T',
            'body' => 'Q',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $user = User::factory()->create();
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $user->id,
            'body' => 'My answer',
            'is_public' => false,
            'first_submitted_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/{$prompt->id}")
            ->assertOk()
            ->assertJsonPath('data.my_response.body', 'My answer');
    }

    public function test_upsert_response_creates_or_updates(): void
    {
        $tenant = $this->tenantWithReflectionsEnabled();
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'T',
            'body' => 'Q',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/{$prompt->id}/response", [
            'body' => 'First draft',
            'is_public' => true,
        ])->assertOk();

        $this->putJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/{$prompt->id}/response", [
            'body' => 'Revised',
            'is_public' => false,
        ])->assertOk();

        $this->assertDatabaseHas('reflection_responses', [
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $user->id,
            'body' => 'Revised',
            'is_public' => false,
        ]);
        $this->assertSame(1, ReflectionResponse::query()->where('reflection_prompt_id', $prompt->id)->where('user_id', $user->id)->count());
    }

    public function test_record_view_sets_timestamps(): void
    {
        $tenant = $this->tenantWithReflectionsEnabled();
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'T',
            'body' => 'Q',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/{$prompt->id}/view")
            ->assertOk()
            ->assertJsonStructure(['data' => ['first_viewed_at', 'last_viewed_at']]);

        $this->postJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/{$prompt->id}/view")
            ->assertOk();
    }
}
