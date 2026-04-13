<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSpaceWelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_space_root_redirects_guest_to_public_catalog(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'adeola',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->get('/'.$tenant->slug)
            ->assertRedirect(route('public.catalog', $tenant));
    }

    public function test_space_root_redirects_authenticated_user_to_learn_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'adeola',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/'.$tenant->slug)
            ->assertRedirect(route('learn.dashboard', $tenant));
    }
}
