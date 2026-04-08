<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_login_shows_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in', false);
    }

    public function test_global_login_redirects_to_continue_when_user_has_exactly_one_space(): void
    {
        $tenant = Tenant::query()->create(['name' => 'One', 'slug' => 'one', 'status' => Tenant::STATUS_ACTIVE]);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('learn.continue', $tenant));
    }

    public function test_global_login_redirects_to_dashboard_when_user_has_no_spaces(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_global_login_redirects_to_dashboard_when_user_has_multiple_spaces(): void
    {
        $t1 = Tenant::query()->create(['name' => 'A', 'slug' => 'a', 'status' => Tenant::STATUS_ACTIVE]);
        $t2 = Tenant::query()->create(['name' => 'B', 'slug' => 'b', 'status' => Tenant::STATUS_ACTIVE]);
        $user = User::factory()->create();
        TenantMembership::query()->create(['tenant_id' => $t1->id, 'user_id' => $user->id, 'role' => 'learner']);
        TenantMembership::query()->create(['tenant_id' => $t2->id, 'user_id' => $user->id, 'role' => 'learner']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_space_login_redirects_to_continue_for_that_tenant(): void
    {
        $tenant = Tenant::query()->create(['name' => 'S', 'slug' => 's', 'status' => Tenant::STATUS_ACTIVE]);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        $this->post('/s/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('learn.continue', $tenant));
    }
}
