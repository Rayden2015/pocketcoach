<?php

namespace App\Http\Controllers\Web;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\SpaceInvite;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AcceptSpaceCoachInviteController extends Controller
{
    public function show(Request $request, Tenant $tenant, string $token): View|RedirectResponse
    {
        $invite = $this->findInvite($tenant, $token);

        if ($invite === null) {
            return view('coach-invite.invalid', [
                'tenant' => $tenant,
                'message' => 'This invitation link is invalid or has already been used.',
            ]);
        }

        if ($invite->isExpired()) {
            return view('coach-invite.invalid', [
                'tenant' => $tenant,
                'message' => 'This invitation has expired. Ask the space owner to send a new one.',
            ]);
        }

        $existingUser = User::query()->where('email', $invite->email)->first();
        $authUser = $request->user();

        if ($authUser !== null && strcasecmp($authUser->email, $invite->email) === 0) {
            return view('coach-invite.accept', [
                'tenant' => $tenant,
                'invite' => $invite,
                'mode' => 'confirm',
                'existingUser' => $existingUser,
            ]);
        }

        if ($authUser !== null && strcasecmp($authUser->email, $invite->email) !== 0) {
            return view('coach-invite.accept', [
                'tenant' => $tenant,
                'invite' => $invite,
                'mode' => 'wrong_user',
                'existingUser' => $existingUser,
            ]);
        }

        if ($existingUser !== null) {
            return view('coach-invite.accept', [
                'tenant' => $tenant,
                'invite' => $invite,
                'mode' => 'login',
                'existingUser' => $existingUser,
            ]);
        }

        return view('coach-invite.accept', [
            'tenant' => $tenant,
            'invite' => $invite,
            'mode' => 'register',
            'existingUser' => null,
        ]);
    }

    public function accept(Request $request, Tenant $tenant, string $token): RedirectResponse
    {
        $invite = $this->findInvite($tenant, $token);

        if ($invite === null || $invite->isExpired()) {
            return redirect()
                ->route('space.coach-invite.show', [$tenant, $token])
                ->with('warning', 'This invitation is no longer valid.');
        }

        $existingUser = User::query()->where('email', $invite->email)->first();
        $authUser = $request->user();

        if ($existingUser === null) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $invite->email,
                'password' => $validated['password'],
            ]);
            event(new Registered($user));
            $this->applyInvite($invite, $user);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->route('coach.programs.index', $tenant)
                ->with('status', 'Welcome! You are now on the coaching team.');
        }

        if ($authUser === null) {
            $validated = $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($validated['password'], $existingUser->password)) {
                return back()->withErrors(['password' => 'Incorrect password.'])->withInput();
            }

            Auth::login($existingUser);
            $request->session()->regenerate();
            $this->applyInvite($invite, $existingUser);

            return redirect()
                ->route('coach.programs.index', $tenant)
                ->with('status', 'You joined the coaching team.');
        }

        if (strcasecmp($authUser->email, $invite->email) !== 0) {
            return back()->withErrors([
                'email' => 'You are signed in as '.$authUser->email.'. Sign out and use '.$invite->email.' to accept this invite.',
            ]);
        }

        $this->applyInvite($invite, $authUser);

        return redirect()
            ->route('coach.programs.index', $tenant)
            ->with('status', 'You joined the coaching team.');
    }

    private function findInvite(Tenant $tenant, string $token): ?SpaceInvite
    {
        return SpaceInvite::query()
            ->where('tenant_id', $tenant->id)
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();
    }

    private function applyInvite(SpaceInvite $invite, User $user): void
    {
        $membership = TenantMembership::query()->firstOrNew([
            'tenant_id' => $invite->tenant_id,
            'user_id' => $user->id,
        ]);

        if ($membership->exists && $membership->role === TenantRole::Owner->value) {
            $invite->forceFill(['accepted_at' => now()])->save();

            return;
        }

        $membership->role = $invite->role;
        $membership->save();

        $invite->forceFill(['accepted_at' => now()])->save();

        SpaceInvite::query()
            ->where('tenant_id', $invite->tenant_id)
            ->where('email', $invite->email)
            ->whereNull('accepted_at')
            ->whereKeyNot($invite->id)
            ->delete();
    }
}
