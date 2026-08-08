<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_device_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/device-tokens', [
            'token' => 'abc123-device-token',
            'platform' => 'ios',
        ])->assertCreated();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'abc123-device-token',
            'platform' => 'ios',
        ]);
    }

    public function test_user_can_remove_device_token(): void
    {
        $user = User::factory()->create();
        DeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'remove-me',
            'platform' => 'android',
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/device-tokens', [
            'token' => 'remove-me',
        ])->assertOk();

        $this->assertDatabaseMissing('device_tokens', [
            'user_id' => $user->id,
            'token' => 'remove-me',
        ]);
    }
}
