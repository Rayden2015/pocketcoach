@php
    use Illuminate\Support\Str;
@endphp

@if ($prompt)
    <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white px-5 py-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">Today&rsquo;s reflection</p>
                @if ($prompt->title)
                    <p class="mt-1 text-lg font-semibold text-stone-900">{{ $prompt->title }}</p>
                @endif
                <p class="mt-2 text-sm text-stone-700">{{ Str::limit(strip_tags($prompt->body), 240) }}</p>
                @if ($prompt->published_at)
                    <p class="mt-2 text-xs text-stone-500">Posted {{ $prompt->published_at->timezone(config('app.timezone'))->format('M j, Y') }}</p>
                @endif
            </div>
            @if ($guest ?? false)
                <p class="text-xs text-stone-600">Log in to respond to your coach&rsquo;s prompt.</p>
            @else
                <div class="flex shrink-0 flex-col items-start gap-2">
                    @if (! ($hasResponse ?? false))
                        <span class="rounded-full bg-amber-600 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">New</span>
                    @endif
                    <a href="{{ route('learn.reflections.show', [$tenant, $prompt]) }}"
                        class="inline-flex rounded-full bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">
                        {{ ($hasResponse ?? false) ? 'View your reflection' : 'Open reflection' }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif
