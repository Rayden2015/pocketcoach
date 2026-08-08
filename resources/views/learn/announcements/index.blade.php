@extends('layouts.app')

@section('title', $tenant->name.' — announcements')

@section('content')
    <div class="mb-6">
        <a href="{{ route('learn.dashboard', $tenant) }}" class="text-sm text-teal-700 hover:underline">← Dashboard</a>
        <h1 class="mt-2 text-2xl font-semibold">Announcements</h1>
    </div>

    <ul class="divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($announcements as $announcement)
            <li class="px-4 py-3">
                <a href="{{ route('learn.announcements.show', [$tenant, $announcement]) }}" class="font-medium text-stone-900 hover:text-teal-800">
                    {{ $announcement->title }}
                </a>
                <p class="mt-1 text-xs text-stone-500">{{ $announcement->published_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No announcements yet.</li>
        @endforelse
    </ul>

    {{ $announcements->links() }}
@endsection
