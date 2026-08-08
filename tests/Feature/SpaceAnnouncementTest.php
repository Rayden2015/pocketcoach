<?php

namespace Tests\Feature;

use App\Models\SpaceAnnouncement;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\SpaceAnnouncementPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SpaceAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_publish_announcement_and_notify_members(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'ann-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create();
        $member = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'role' => 'learner',
        ]);

        $this->actingAs($coach)->post(route('coach.announcements.store', $tenant), [
            'title' => 'Welcome back',
            'body' => 'New content is live.',
            'publish_now' => '1',
        ])->assertRedirect(route('coach.announcements.index', $tenant));

        $this->assertDatabaseHas('space_announcements', [
            'tenant_id' => $tenant->id,
            'title' => 'Welcome back',
            'is_published' => true,
        ]);

        Notification::assertSentTo($member, SpaceAnnouncementPublishedNotification::class);
    }

    public function test_learner_can_view_published_announcement(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'ann-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $announcement = SpaceAnnouncement::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $learner->id,
            'title' => 'Hello',
            'body' => 'World',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learn.announcements.show', [$tenant, $announcement]))
            ->assertOk()
            ->assertSee('Hello', false)
            ->assertSee('World', false);
    }
}
