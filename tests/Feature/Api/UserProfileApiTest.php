<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_requires_auth(): void
    {
        $this->putJson('/api/v1/profile', [
            'name' => 'N',
        ])->assertUnauthorized();
    }

    public function test_profile_update_persists_fields(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'headline' => null]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'name' => 'New Name',
            'headline' => 'Coach',
            'bio' => 'Hello',
            'timezone' => 'UTC',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.headline', 'Coach');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'headline' => 'Coach',
        ]);
    }

    public function test_profile_update_rejects_invalid_timezone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'name' => 'Valid',
            'timezone' => 'Not/A_Real_Zone',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    }

    public function test_profile_update_rejects_invalid_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'name' => 'Valid',
            'locale' => 'xx',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }
}
