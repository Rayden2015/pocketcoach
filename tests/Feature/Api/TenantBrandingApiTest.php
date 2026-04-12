<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBrandingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_branding_returns_json_for_active_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Branded',
            'slug' => 'brand',
            'status' => Tenant::STATUS_ACTIVE,
            'branding' => ['primary' => '#0d9488'],
            'settings' => ['catalog' => ['intro_markdown' => 'Welcome']],
        ]);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/branding")
            ->assertOk()
            ->assertJsonPath('data.slug', 'brand')
            ->assertJsonPath('data.name', 'Branded')
            ->assertJsonPath('data.branding.primary', '#0d9488');
    }

    public function test_branding_returns_404_for_inactive_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Gone',
            'slug' => 'gone',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/branding")
            ->assertNotFound();
    }
}
