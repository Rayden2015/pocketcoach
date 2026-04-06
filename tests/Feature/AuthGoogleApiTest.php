<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthGoogleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_auth_returns_503_when_not_configured(): void
    {
        config(['services.google.client_id' => '']);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'dummy',
        ])->assertStatus(503);
    }

    public function test_google_auth_rejects_garbage_token_when_configured(): void
    {
        config(['services.google.client_id' => '123456789-test.apps.googleusercontent.com']);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'not-a-valid-jwt',
        ])->assertStatus(422);
    }
}
