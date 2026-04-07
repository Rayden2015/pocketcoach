<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantRegisteredUserController extends Controller
{
    public function create(Tenant $tenant): View
    {
        return view('auth.tenant-register', ['tenant' => $tenant]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        event(new Registered($user));

        TenantMembership::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'learner',
            ],
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('learn.catalog', $tenant));
    }
}
