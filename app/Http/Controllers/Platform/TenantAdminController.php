<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantAdminController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()->orderBy('name')->paginate(20);

        return view('platform.tenants.index', ['tenants' => $tenants]);
    }

    public function create(): View
    {
        return view('platform.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $reserved = array_map('strtolower', config('tenancy.reserved_slugs', []));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn($reserved),
                Rule::unique('tenants', 'slug'),
            ],
            'status' => ['required', Rule::in([Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED])],
            'custom_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant = Tenant::query()->create([
            'name' => $validated['name'],
            'slug' => Str::lower($validated['slug']),
            'status' => $validated['status'],
            'custom_domain' => $validated['custom_domain'] ?? null,
            'branding' => ['primary' => '#0d9488'],
        ]);

        return redirect()
            ->route('platform.tenants.edit', ['adminTenant' => $tenant->id])
            ->with('status', 'Space created. A coach can claim it via Create a space using this slug, or assign an owner later.');
    }

    public function edit(Tenant $adminTenant): View
    {
        return view('platform.tenants.edit', ['record' => $adminTenant]);
    }

    public function update(Request $request, Tenant $adminTenant): RedirectResponse
    {
        $reserved = array_map('strtolower', config('tenancy.reserved_slugs', []));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn($reserved),
                Rule::unique('tenants', 'slug')->ignore($adminTenant->id),
            ],
            'status' => ['required', Rule::in([Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED])],
            'custom_domain' => ['nullable', 'string', 'max:255'],
            'branding_json' => ['nullable', 'string', 'max:65535'],
        ]);

        $branding = $adminTenant->branding ?? [];
        $rawBranding = isset($validated['branding_json']) ? trim((string) $validated['branding_json']) : '';
        if ($rawBranding !== '') {
            $decoded = json_decode($rawBranding, true);
            if (! is_array($decoded)) {
                return back()->withErrors(['branding_json' => 'Invalid JSON.'])->withInput();
            }
            $branding = $decoded;
        }

        $adminTenant->fill([
            'name' => $validated['name'],
            'slug' => Str::lower($validated['slug']),
            'status' => $validated['status'],
            'custom_domain' => $validated['custom_domain'] ?: null,
            'branding' => $branding,
        ])->save();

        return redirect()->route('platform.tenants.edit', ['adminTenant' => $adminTenant->id])->with('status', 'Space updated.');
    }
}
