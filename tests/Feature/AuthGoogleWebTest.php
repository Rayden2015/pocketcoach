<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthGoogleWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_login_shows_google_button_when_client_id_configured(): void
    {
        config([
            'services.google.client_id' => '123456789-abc.apps.googleusercontent.com',
            'services.google.client_secret' => 'secret',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Continue with Google', false)
            ->assertSee('/auth/google', false);
    }

    public function test_global_login_hides_google_button_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Continue with Google', false);
    }

    public function test_global_register_shows_google_button_when_configured(): void
    {
        config([
            'services.google.client_id' => '123456789-abc.apps.googleusercontent.com',
            'services.google.client_secret' => 'secret',
        ]);

        $this->get('/register')
            ->assertOk()
            ->assertSee('Sign up with Google', false);
    }

    public function test_space_login_shows_google_button_when_configured(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        config([
            'services.google.client_id' => '123456789-abc.apps.googleusercontent.com',
            'services.google.client_secret' => 'secret',
        ]);

        $this->get("/{$tenant->slug}/login")
            ->assertOk()
            ->assertSee('Continue with Google', false)
            ->assertSee('tenant=t', false);
    }

    public function test_space_register_shows_google_button_when_configured(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        config([
            'services.google.client_id' => '123456789-abc.apps.googleusercontent.com',
            'services.google.client_secret' => 'secret',
        ]);

        $this->get("/{$tenant->slug}/register")
            ->assertOk()
            ->assertSee('Continue with Google', false);
    }

    public function test_get_auth_google_redirect_returns_404_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get('/auth/google')->assertNotFound();
    }
}
