@extends('layouts.app')

@section('title', $tenant->name.' — announcements')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-stone-600">Post updates to everyone in this space. Published announcements notify members by email and in-app alerts.</p>
        <a href="{{ route('coach.announcements.create', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New announcement</a>
    </div>

    <ul class="mt-2 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($announcements as $announcement)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <span class="font-medium text-stone-900">{{ $announcement->title }}</span>
                    <span class="ml-2 text-xs {{ $announcement->is_published ? 'text-teal-700' : 'text-stone-400' }}">
                        {{ $announcement->is_published ? 'Published' : 'Draft' }}
                    </span>
                    @if ($announcement->published_at)
                        <span class="ml-2 text-xs text-stone-500">{{ $announcement->published_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @if (! $announcement->is_published)
                        <form method="post" action="{{ route('coach.announcements.publish', [$tenant, $announcement]) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-teal-700 hover:underline">Publish</button>
                        </form>
                    @endif
                    <a href="{{ route('coach.announcements.edit', [$tenant, $announcement]) }}" class="text-sm text-teal-700 hover:underline">Edit</a>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No announcements yet.</li>
        @endforelse
    </ul>
@endsection
