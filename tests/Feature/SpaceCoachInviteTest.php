<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\SpaceInvite;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\SpaceCoachInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SpaceCoachInviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, owner: User}
     */
    private function spaceWithOwner(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Invite Space',
            'slug' => 'invite-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => TenantRole::Owner->value,
        ]);

        return compact('tenant', 'owner');
    }

    public function test_owner_can_send_coach_invite(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->spaceWithOwner();

        $this->actingAs($owner)
            ->post(route('coach.team.invites.store', $tenant), [
                'email' => 'newcoach@example.com',
                'role' => 'instructor',
            ])
            ->assertRedirect(route('coach.team.index', $tenant));

        $invite = SpaceInvite::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($invite);
        $this->assertSame('newcoach@example.com', $invite->email);
        $this->assertSame('instructor', $invite->role);

        Notification::assertSentOnDemand(SpaceCoachInviteNotification::class);
    }

    public function test_admin_can_send_coach_invite(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->spaceWithOwner();
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantRole::Admin->value,
        ]);

        $this->actingAs($admin)
            ->post(route('coach.team.invites.store', $tenant), [
                'email' => 'another@example.com',
                'role' => 'admin',
            ])
            ->assertRedirect(route('coach.team.index', $tenant));

        $this->assertDatabaseHas('space_invites', [
            'tenant_id' => $tenant->id,
            'email' => 'another@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_instructor_cannot_manage_team(): void
    {
        ['tenant' => $tenant] = $this->spaceWithOwner();
        $instructor = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $instructor->id,
            'role' => TenantRole::Instructor->value,
        ]);

        $this->actingAs($instructor)
            ->get(route('coach.team.index', $tenant))
            ->assertForbidden();

        $this->actingAs($instructor)
            ->post(route('coach.team.invites.store', $tenant), [
                'email' => 'x@example.com',
                'role' => 'instructor',
            ])
            ->assertForbidden();
    }

    public function test_new_user_can_accept_invite_and_become_staff(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->spaceWithOwner();
        $invite = SpaceInvite::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'fresh@example.com',
            'role' => 'instructor',
            'invited_by_user_id' => $owner->id,
            'token' => SpaceInvite::generateToken(),
            'expires_at' => now()->addDay(),
        ]);

        $this->post(route('space.coach-invite.accept', [$tenant, $invite->token]), [
            'name' => 'Fresh Coach',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('coach.programs.index', $tenant));

        $user = User::query()->where('email', 'fresh@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'instructor',
        ]);
        $this->assertNotNull($invite->fresh()->accepted_at);
    }

    public function test_expired_invite_cannot_be_accepted(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->spaceWithOwner();
        $invite = SpaceInvite::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'late@example.com',
            'role' => 'instructor',
            'invited_by_user_id' => $owner->id,
            'token' => SpaceInvite::generateToken(),
            'expires_at' => now()->subHour(),
        ]);

        $this->get(route('space.coach-invite.show', [$tenant, $invite->token]))
            ->assertOk()
            ->assertSee('expired', false);

        $this->post(route('space.coach-invite.accept', [$tenant, $invite->token]), [
            'name' => 'Too Late',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'late@example.com']);
    }

    public function test_learner_is_promoted_when_accepting_invite(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->spaceWithOwner();
        $learner = User::factory()->create([
            'email' => 'learner-promo@example.com',
            'password' => 'password',
        ]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => TenantRole::Learner->value,
        ]);
        $invite = SpaceInvite::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'learner-promo@example.com',
            'role' => 'instructor',
            'invited_by_user_id' => $owner->id,
            'token' => SpaceInvite::generateToken(),
            'expires_at' => now()->addDay(),
        ]);

        $this->post(route('space.coach-invite.accept', [$tenant, $invite->token]), [
            'password' => 'password',
        ])->assertRedirect(route('coach.programs.index', $tenant));

        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'instructor',
        ]);
        $this->assertSame(1, TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $learner->id)->count());
    }

    public function test_cannot_invite_existing_staff(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->spaceWithOwner();
        $instructor = User::factory()->create(['email' => 'already@example.com']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $instructor->id,
            'role' => TenantRole::Instructor->value,
        ]);

        $this->actingAs($owner)
            ->from(route('coach.team.index', $tenant))
            ->post(route('coach.team.invites.store', $tenant), [
                'email' => 'already@example.com',
                'role' => 'admin',
            ])
            ->assertRedirect(route('coach.team.index', $tenant))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }
}
