<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionPromptView;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantEngagementSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnReflectionController extends Controller
{
    public function show(Tenant $tenant, ReflectionPrompt $reflection_prompt): View
    {
        abort_unless($reflection_prompt->tenant_id === $tenant->id, 404);
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);
        abort_unless($reflection_prompt->is_published && $reflection_prompt->published_at !== null, 404);

        $user = request()->user();
        abort_unless($user instanceof User, 403);

        $this->touchPromptView($reflection_prompt, $user);

        $viewRow = ReflectionPromptView::query()
            ->where('reflection_prompt_id', $reflection_prompt->id)
            ->where('user_id', $user->id)
            ->first();

        $responseRow = ReflectionResponse::query()
            ->where('reflection_prompt_id', $reflection_prompt->id)
            ->where('user_id', $user->id)
            ->withCount('conversationMessages')
            ->first();

        $publicPeerResponses = ReflectionResponse::query()
            ->where('reflection_prompt_id', $reflection_prompt->id)
            ->where('is_public', true)
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->where('user_id', '!=', $user->id)
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('learn.reflection', [
            'tenant' => $tenant,
            'prompt' => $reflection_prompt,
            'viewRow' => $viewRow,
            'responseRow' => $responseRow,
            'publicPeerResponses' => $publicPeerResponses,
        ]);
    }

    public function updateResponse(Request $request, Tenant $tenant, ReflectionPrompt $reflection_prompt): RedirectResponse
    {
        abort_unless($reflection_prompt->tenant_id === $tenant->id, 404);
        abort_unless(TenantEngagementSettings::reflections($tenant)['enabled'], 404);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
            'is_public' => ['nullable', 'boolean'],
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

        return redirect()
            ->route('learn.reflections.show', [$tenant, $reflection_prompt])
            ->with('status', 'Reflection saved.');
    }

    private function touchPromptView(ReflectionPrompt $prompt, User $user): void
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
