<?php

namespace App\Http\Controllers\Web\Coach;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\SpaceInvite;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\SpaceCoachInviteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpaceTeamController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $staff = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', TenantRole::staffValues())
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->get();

        $pendingInvites = SpaceInvite::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        $currentRole = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $request->user()?->id)
            ->value('role');

        return view('coach.team.index', [
            'tenant' => $tenant,
            'staff' => $staff,
            'pendingInvites' => $pendingInvites,
            'currentRole' => $currentRole,
        ]);
    }

    public function storeInvite(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(TenantRole::invitableStaffValues())],
        ]);

        $email = strtolower(trim($validated['email']));
        $role = $validated['role'];

        $existingUser = User::query()->where('email', $email)->first();
        if ($existingUser !== null) {
            $existingMembership = TenantMembership::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($existingMembership !== null && in_array($existingMembership->role, TenantRole::staffValues(), true)) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'That person is already on the coaching team.']);
            }
        }

        SpaceInvite::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        $invite = SpaceInvite::query()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => $role,
            'invited_by_user_id' => $request->user()->id,
            'token' => SpaceInvite::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);
        $invite->load(['tenant', 'invitedBy']);

        Log::info('space_invite.created', [
            'invite_id' => $invite->id,
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'email' => $email,
            'role' => $role,
            'invited_by_user_id' => $request->user()->id,
            'expires_at' => $invite->expires_at->toIso8601String(),
            'notification' => SpaceCoachInviteNotification::class,
            'notification_queued' => true,
            'queue_connection' => config('queue.default'),
        ]);

        Notification::route('mail', $email)
            ->notify(new SpaceCoachInviteNotification($invite));

        return redirect()
            ->route('coach.team.index', $tenant)
            ->with('status', 'Invitation sent to '.$email.'.');
    }

    public function revokeInvite(Tenant $tenant, SpaceInvite $invite): RedirectResponse
    {
        abort_unless($invite->tenant_id === $tenant->id, 404);
        abort_unless($invite->accepted_at === null, 404);

        $invite->delete();

        Log::info('space_invite.revoked', [
            'invite_id' => $invite->id,
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'email' => $invite->email,
            'role' => $invite->role,
        ]);

        return redirect()
            ->route('coach.team.index', $tenant)
            ->with('status', 'Invitation cancelled.');
    }

    public function updateMember(Request $request, Tenant $tenant, TenantMembership $membership): RedirectResponse
    {
        abort_unless($membership->tenant_id === $tenant->id, 404);
        abort_unless(in_array($membership->role, TenantRole::staffValues(), true), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in(TenantRole::invitableStaffValues())],
        ]);

        if ($membership->role === TenantRole::Owner->value) {
            return back()->withErrors(['role' => 'The space owner’s role cannot be changed here.']);
        }

        if ((int) $membership->user_id === (int) $request->user()->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $membership->update(['role' => $validated['role']]);

        return redirect()
            ->route('coach.team.index', $tenant)
            ->with('status', 'Team member role updated.');
    }

    public function removeMember(Request $request, Tenant $tenant, TenantMembership $membership): RedirectResponse
    {
        abort_unless($membership->tenant_id === $tenant->id, 404);
        abort_unless(in_array($membership->role, TenantRole::staffValues(), true), 404);

        if ($membership->role === TenantRole::Owner->value) {
            return back()->withErrors(['membership' => 'The space owner cannot be removed.']);
        }

        if ((int) $membership->user_id === (int) $request->user()->id) {
            return back()->withErrors(['membership' => 'You cannot remove yourself from the team.']);
        }

        $membership->update(['role' => TenantRole::Learner->value]);

        return redirect()
            ->route('coach.team.index', $tenant)
            ->with('status', 'Team member removed from coaching staff (kept as a learner in this space).');
    }
}
