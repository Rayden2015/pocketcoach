<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserNotificationWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_json_requires_authentication(): void
    {
        $this->getJson(route('notifications.unread-count'))
            ->assertUnauthorized();
    }

    public function test_unread_count_and_list_for_session_user(): void
    {
        $user = User::factory()->create();
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'title' => 'Hello',
                'url' => 'https://example.com/here',
                'body_preview' => 'Preview line',
            ],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.title', 'Hello')
            ->assertJsonPath('data.0.url', 'https://example.com/here')
            ->assertJsonPath('data.0.preview', 'Preview line');
    }

    public function test_mark_as_read_via_web_route(): void
    {
        $user = User::factory()->create();
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'X'],
        ]);

        $this->actingAs($user)
            ->patchJson(route('notifications.read', ['id' => $id]))
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        $this->assertNotNull($user->notifications()->whereKey($id)->value('read_at'));
    }

    public function test_mark_all_as_read_via_web_route(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 2) as $i) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\TestNotification',
                'data' => ['i' => $i],
            ]);
        }

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('marked', 2);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
