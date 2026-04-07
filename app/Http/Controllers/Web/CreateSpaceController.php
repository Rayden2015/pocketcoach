<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CreateSpaceController extends Controller
{
    public function create(): View
    {
        return view('create-space');
    }

    public function store(Request $request): RedirectResponse
    {
        $reserved = array_map('strtolower', config('tenancy.reserved_slugs', []));

        $validated = $request->validate([
            'space_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn($reserved),
                Rule::unique('tenants', 'slug'),
            ],
            'welcome_headline' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $slug = Str::lower($validated['slug']);

        $user = null;
        $tenant = null;

        DB::transaction(function () use (&$user, &$tenant, $validated, $slug): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            event(new Registered($user));

            $tenant = Tenant::query()->create([
                'name' => $validated['space_name'],
                'slug' => $slug,
                'status' => Tenant::STATUS_ACTIVE,
                'branding' => array_filter([
                    'primary' => $validated['primary_color'] ?? '#0d9488',
                    'accent' => '#f59e0b',
                    'welcome_headline' => $validated['welcome_headline'] ?? null,
                ]),
            ]);

            TenantMembership::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'owner',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('coach.programs.index', $tenant)
            ->with('status', 'Your space is live. Share: '.$tenant->publicUrl());
    }
}
