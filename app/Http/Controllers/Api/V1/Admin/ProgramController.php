<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $programs]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
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
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $slug = $validated['slug'] ?? $this->uniqueProgramSlug($tenant->id, Str::slug($validated['title']));

        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json(['data' => $program], 201);
    }

    public function show(Tenant $tenant, Program $program): JsonResponse
    {
        abort_unless($program->tenant_id === $tenant->id, 404);

        return response()->json(['data' => $program]);
    }

    public function update(Request $request, Tenant $tenant, Program $program): JsonResponse
    {
        abort_unless($program->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('programs', 'slug')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($program->id),
            ],
            'summary' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $program->fill($validated);
        $program->save();

        return response()->json(['data' => $program->fresh()]);
    }

    public function destroy(Tenant $tenant, Program $program): JsonResponse
    {
        abort_unless($program->tenant_id === $tenant->id, 404);
        $program->delete();

        return response()->json(['message' => 'Deleted.']);
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
