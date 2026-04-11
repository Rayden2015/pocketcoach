<?php

namespace App\Http\Controllers\Web;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExtraSpaceController extends Controller
{
    public function create(): View
    {
        return view('spaces.create-extra');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

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
        ]);

        $slug = Str::lower($validated['slug']);

        $tenant = DB::transaction(function () use ($validated, $slug, $user): Tenant {
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
                'role' => TenantRole::Owner->value,
            ]);

            return $tenant;
        });

        return redirect()
            ->route('coach.programs.index', $tenant)
            ->with('status', 'Space created. Share: '.$tenant->publicUrl());
    }
}
