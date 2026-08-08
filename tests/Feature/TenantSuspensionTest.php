<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_space_catalog_returns_not_available(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Paused',
            'slug' => 'paused-space',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->get('/'.$tenant->slug.'/catalog')
            ->assertNotFound();
    }

    public function test_suspended_space_branding_api_returns_not_available(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Paused',
            'slug' => 'paused-space',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $this->getJson('/api/v1/tenants/'.$tenant->slug.'/branding')
            ->assertNotFound()
            ->assertJsonPath('message', 'This space is not available.');
    }

    public function test_active_space_catalog_loads(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Live',
            'slug' => 'live-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->get('/'.$tenant->slug.'/catalog')->assertOk();
    }
}
