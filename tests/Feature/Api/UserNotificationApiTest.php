<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_read_sets_read_at(): void
    {
        $user = User::factory()->create();
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Hello'],
        ]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/notifications/{$id}");
        $response->assertOk()
            ->assertJsonPath('data.id', $id);
        $this->assertNotNull($response->json('data.read_at'));

        $this->assertNotNull($user->notifications()->whereKey($id)->value('read_at'));
    }

    public function test_mark_as_read_returns_404_for_other_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $id = (string) Str::uuid();
        $owner->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [],
        ]);
        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/notifications/{$id}")
            ->assertNotFound();
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 2) as $i) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\TestNotification',
                'data' => ['i' => $i],
            ]);
        }
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('marked', 2);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_index_includes_title_preview_and_url(): void
    {
        $user = User::factory()->create();
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'title' => 'Hello',
                'body_preview' => 'Line one',
                'url' => 'https://example.com/x',
            ],
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.title', 'Hello')
            ->assertJsonPath('data.0.preview', 'Line one')
            ->assertJsonPath('data.0.url', 'https://example.com/x');
    }
}
