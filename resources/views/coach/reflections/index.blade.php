@extends('layouts.app')

@section('title', $tenant->name.' — reflections')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-stone-600">Publish now or <strong>schedule</strong> (default 7:00 app time). Scheduled posts need <code class="rounded bg-stone-100 px-1 text-xs">schedule:work</code> or cron.</p>
        <a href="{{ route('coach.reflections.create', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New prompt</a>
    </div>

    <ul class="mt-2 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($prompts as $prompt)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <span class="font-medium text-stone-900">{{ $prompt->title ?: 'Untitled' }}</span>
                    <span class="ml-2 text-xs {{ $prompt->is_published ? 'text-teal-700' : ($prompt->scheduled_publish_at ? 'text-amber-800' : 'text-stone-400') }}">
                        @if ($prompt->is_published)
                            Published
                        @elseif ($prompt->scheduled_publish_at)
                            Scheduled
                        @else
                            Draft
                        @endif
                    </span>
                    @if ($prompt->is_published && $prompt->published_at)
                        <span class="ml-2 text-xs text-stone-500">{{ $prompt->published_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                    @elseif ($prompt->scheduled_publish_at && ! $prompt->is_published)
                        <span class="ml-2 text-xs text-stone-500">{{ $prompt->scheduled_publish_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
                <a href="{{ route('coach.reflections.edit', [$tenant, $prompt]) }}" class="text-sm text-teal-700 hover:underline">Edit</a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No reflection prompts yet.</li>
        @endforelse
    </ul>
@endsection
