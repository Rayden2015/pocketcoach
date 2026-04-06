<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token_and_user(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'API Learner',
            'email' => 'api.learner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'api.learner@example.com');

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('users', ['email' => 'api.learner@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'X',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'exists@example.com',
            'password' => Hash::make('correct'),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'exists@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_returns_token(): void
    {
        User::factory()->create([
            'email' => 'in@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'in@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'in@example.com');
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('email', 'me@example.com');
    }

    public function test_logout_deletes_current_personal_access_token(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('api')->plainTextToken;
        $this->assertSame(1, $user->fresh()->tokens()->count());

        $this->postJson('/api/v1/logout', [], [
            'Authorization' => 'Bearer '.$plain,
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());

        $this->app->make('auth')->forgetGuards();

        $this->withToken($plain)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
