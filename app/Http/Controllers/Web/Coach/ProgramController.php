<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('sort_order')
            ->get();

        return view('coach.programs.index', [
            'tenant' => $tenant,
            'programs' => $programs,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        return view('coach.programs.create', ['tenant' => $tenant]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('programs', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'summary' => ['nullable', 'string', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $slug = ! empty($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->uniqueProgramSlug($tenant->id, Str::slug($validated['title']));

        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('coach.programs.index', $tenant)
            ->with('status', 'Program created.');
    }

    public function edit(Tenant $tenant, Program $program): View
    {
        abort_unless($program->tenant_id === $tenant->id, 404);

        return view('coach.programs.edit', [
            'tenant' => $tenant,
            'program' => $program,
        ]);
    }

    public function update(Request $request, Tenant $tenant, Program $program): RedirectResponse
    {
        abort_unless($program->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('programs', 'slug')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($program->id),
            ],
            'summary' => ['nullable', 'string', 'max:65535'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $program->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);
        $program->save();

        return redirect()
            ->route('coach.programs.index', $tenant)
            ->with('status', 'Program updated.');
    }

    public function destroy(Tenant $tenant, Program $program): RedirectResponse
    {
        abort_unless($program->tenant_id === $tenant->id, 404);
        $program->delete();

        return redirect()
            ->route('coach.programs.index', $tenant)
            ->with('status', 'Program deleted.');
    }

    private function uniqueProgramSlug(int $tenantId, string $base): string
    {
        $slug = $base;
        $i = 1;
        while (Program::query()->where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
