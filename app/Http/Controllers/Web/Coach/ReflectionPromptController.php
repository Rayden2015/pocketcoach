<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionPromptView;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReflectionPromptController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $prompts = ReflectionPrompt::query()
            ->where('tenant_id', $tenant->id)
            ->withCount(['views', 'responses'])
            ->orderByRaw('COALESCE(published_at, scheduled_publish_at) DESC')
            ->orderByDesc('id')
            ->get();

        return view('coach.reflections.index', [
            'tenant' => $tenant,
            'prompts' => $prompts,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        $tz = config('app.timezone');
        $nextSeven = Carbon::now($tz)->setTime(7, 0, 0);
        if ($nextSeven->isPast()) {
            $nextSeven->addDay();
        }

        return view('coach.reflections.create', [
            'tenant' => $tenant,
            'defaultScheduleDate' => $nextSeven->format('Y-m-d'),
            'defaultScheduleTime' => '07:00',
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'publish_timing' => ['required', Rule::in(['now', 'schedule'])],
            'scheduled_date' => ['exclude_if:publish_timing,now', 'required', 'date'],
            'scheduled_time' => ['exclude_if:publish_timing,now', 'required', 'date_format:H:i'],
        ]);

        $timing = $validated['publish_timing'];
        $publishAttrs = $this->publishAttributesFromTiming($request, $timing);
        $imagePath = $this->storeImageFromRequest($request, $tenant);

        ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $request->user()?->id,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'image_disk_path' => $imagePath,
            ...$publishAttrs,
        ]);

        $status = 'Reflection prompt saved.';
        if (! $publishAttrs['is_published'] && $publishAttrs['scheduled_publish_at'] !== null) {
            $status = 'Reflection scheduled for '.$publishAttrs['scheduled_publish_at']->timezone(config('app.timezone'))->format('M j, Y g:i A').'. Learners will be notified by email and in-app alerts when it goes live.';
        } elseif ($publishAttrs['is_published']) {
            $status = 'Reflection published. Learners are being notified by email and in-app alerts.';
        }

        return redirect()
            ->route('coach.reflections.index', $tenant)
            ->with('status', $status);
    }

    public function edit(Tenant $tenant, ReflectionPrompt $reflection): View
    {
        abort_unless($reflection->tenant_id === $tenant->id, 404);

        $tz = config('app.timezone');
        $defaultScheduleDate = old(
            'scheduled_date',
            $reflection->scheduled_publish_at?->copy()->timezone($tz)->format('Y-m-d')
                ?? Carbon::now($tz)->setTime(7, 0, 0)->addDay()->format('Y-m-d'),
        );
        $defaultScheduleTime = old(
            'scheduled_time',
            $reflection->scheduled_publish_at?->copy()->timezone($tz)->format('H:i') ?? '07:00',
        );

        $viewers = collect();
        $respondedUserIds = collect();
        if ($reflection->is_published) {
            $viewers = ReflectionPromptView::query()
                ->where('reflection_prompt_id', $reflection->id)
                ->with('user:id,name,email')
                ->get()
                ->sortBy(fn (ReflectionPromptView $view) => strtolower($view->user?->name ?? $view->user?->email ?? ''))
                ->values();

            $respondedUserIds = $reflection->responses()
                ->whereNotNull('first_submitted_at')
                ->pluck('user_id');
        }

        return view('coach.reflections.edit', [
            'tenant' => $tenant,
            'prompt' => $reflection,
            'defaultScheduleDate' => $defaultScheduleDate,
            'defaultScheduleTime' => $defaultScheduleTime,
            'viewers' => $viewers,
            'respondedUserIds' => $respondedUserIds,
        ]);
    }

    public function update(Request $request, Tenant $tenant, ReflectionPrompt $reflection): RedirectResponse
    {
        abort_unless($reflection->tenant_id === $tenant->id, 404);

        if ($reflection->is_published) {
            $validated = $request->validate([
                'title' => ['nullable', 'string', 'max:255'],
                'body' => ['required', 'string', 'max:65535'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
                'remove_image' => ['nullable', 'boolean'],
                'is_published' => ['nullable', 'boolean'],
            ]);

            $publish = $request->boolean('is_published');
            $imagePath = $this->imagePathAfterUpdate($request, $tenant, $reflection);
            $reflection->fill([
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'],
                'image_disk_path' => $imagePath,
                'is_published' => $publish,
                'published_at' => $publish
                    ? ($reflection->published_at ?? now())
                    : null,
                'scheduled_publish_at' => $publish ? $reflection->scheduled_publish_at : null,
            ]);
            $reflection->save();

            return redirect()
                ->route('coach.reflections.index', $tenant)
                ->with('status', 'Reflection prompt updated.');
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
            'publish_timing' => ['required', Rule::in(['now', 'schedule'])],
            'scheduled_date' => ['exclude_if:publish_timing,now', 'required', 'date'],
            'scheduled_time' => ['exclude_if:publish_timing,now', 'required', 'date_format:H:i'],
        ]);

        $publishAttrs = $this->publishAttributesFromTiming($request, $validated['publish_timing']);
        $imagePath = $this->imagePathAfterUpdate($request, $tenant, $reflection);
        $reflection->fill([
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'image_disk_path' => $imagePath,
            ...$publishAttrs,
        ]);
        $reflection->save();

        $status = 'Reflection prompt updated.';
        if (! $publishAttrs['is_published'] && $publishAttrs['scheduled_publish_at'] !== null) {
            $status = 'Schedule updated for '.$publishAttrs['scheduled_publish_at']->timezone(config('app.timezone'))->format('M j, Y g:i A').'. Learners will be notified when it goes live.';
        } elseif ($publishAttrs['is_published']) {
            $status = 'Reflection published. Learners are being notified by email and in-app alerts.';
        }

        return redirect()
            ->route('coach.reflections.index', $tenant)
            ->with('status', $status);
    }

    public function destroy(Tenant $tenant, ReflectionPrompt $reflection): RedirectResponse
    {
        abort_unless($reflection->tenant_id === $tenant->id, 404);
        $reflection->delete();

        return redirect()
            ->route('coach.reflections.index', $tenant)
            ->with('status', 'Reflection prompt deleted.');
    }

    /**
     * @return array{is_published: bool, published_at: Carbon|null, scheduled_publish_at: Carbon|null}
     */
    private function publishAttributesFromTiming(Request $request, string $timing): array
    {
        if ($timing === 'now') {
            return [
                'is_published' => true,
                'published_at' => now(),
                'scheduled_publish_at' => null,
            ];
        }

        $tz = config('app.timezone');
        $date = $request->input('scheduled_date');
        $time = $request->input('scheduled_time', '07:00');
        $at = Carbon::parse($date.' '.$time, $tz);

        if ($at->lte(now())) {
            return [
                'is_published' => true,
                'published_at' => now(),
                'scheduled_publish_at' => null,
            ];
        }

        return [
            'is_published' => false,
            'published_at' => null,
            'scheduled_publish_at' => $at,
        ];
    }

    private function storeImageFromRequest(Request $request, Tenant $tenant): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $file->store("reflections/{$tenant->id}", 'public');
    }

    private function imagePathAfterUpdate(Request $request, Tenant $tenant, ReflectionPrompt $reflection): ?string
    {
        if ($request->boolean('remove_image') && $reflection->image_disk_path) {
            Storage::disk('public')->delete($reflection->image_disk_path);

            return null;
        }

        if ($request->hasFile('image')) {
            if ($reflection->image_disk_path) {
                Storage::disk('public')->delete($reflection->image_disk_path);
            }

            return $this->storeImageFromRequest($request, $tenant);
        }

        return $reflection->image_disk_path;
    }
}
