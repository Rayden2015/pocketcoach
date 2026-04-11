<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionPromptView;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantEngagementSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerReflectionController extends Controller
{
    public function latest(Tenant $tenant): JsonResponse
    {
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);

        $prompt = ReflectionPrompt::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->first();

        if ($prompt === null) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $this->serializePrompt($prompt)]);
    }

    public function show(Request $request, Tenant $tenant, ReflectionPrompt $reflection_prompt): JsonResponse
    {
        abort_unless($reflection_prompt->tenant_id === $tenant->id, 404);
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);
        abort_unless($reflection_prompt->is_published && $reflection_prompt->published_at !== null, 404);

        $data = $this->serializePrompt($reflection_prompt);
        $user = $request->user();
        if ($user !== null) {
            $mine = ReflectionResponse::query()
                ->where('reflection_prompt_id', $reflection_prompt->id)
                ->where('user_id', $user->id)
                ->first();
            $data['my_response'] = $mine === null ? null : [
                'body' => $mine->body,
                'is_public' => $mine->is_public,
                'first_submitted_at' => $mine->first_submitted_at?->toIso8601String(),
                'updated_at' => $mine->updated_at?->toIso8601String(),
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function recordView(Request $request, Tenant $tenant, ReflectionPrompt $reflection_prompt): JsonResponse
    {
        abort_unless($reflection_prompt->tenant_id === $tenant->id, 404);
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $this->touchView($reflection_prompt, $user);

        $row = ReflectionPromptView::query()
            ->where('reflection_prompt_id', $reflection_prompt->id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'data' => [
                'first_viewed_at' => $row?->first_viewed_at?->toIso8601String(),
                'last_viewed_at' => $row?->last_viewed_at?->toIso8601String(),
            ],
        ]);
    }

    public function upsertResponse(Request $request, Tenant $tenant, ReflectionPrompt $reflection_prompt): JsonResponse
    {
        abort_unless($reflection_prompt->tenant_id === $tenant->id, 404);
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $now = now();
        $response = ReflectionResponse::query()->firstOrNew([
            'reflection_prompt_id' => $reflection_prompt->id,
            'user_id' => $user->id,
        ]);
        $response->body = $validated['body'];
        $response->is_public = $request->boolean('is_public');
        if ($response->first_submitted_at === null) {
            $response->first_submitted_at = $now;
        }
        $response->save();

        return response()->json([
            'data' => [
                'body' => $response->body,
                'is_public' => $response->is_public,
                'first_submitted_at' => $response->first_submitted_at?->toIso8601String(),
                'updated_at' => $response->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePrompt(ReflectionPrompt $prompt): array
    {
        return [
            'id' => $prompt->id,
            'title' => $prompt->title,
            'body' => $prompt->body,
            'published_at' => $prompt->published_at?->toIso8601String(),
        ];
    }

    private function touchView(ReflectionPrompt $prompt, User $user): void
    {
        $row = ReflectionPromptView::query()->firstOrNew([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $user->id,
        ]);

        $now = now();
        if (! $row->exists) {
            $row->first_viewed_at = $now;
        }
        $row->last_viewed_at = $now;
        $row->save();
    }
}
