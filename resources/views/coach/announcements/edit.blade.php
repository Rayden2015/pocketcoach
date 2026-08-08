@extends('layouts.app')

@section('title', $tenant->name.' — edit announcement')

@section('content')
    @include('coach.partials.header')

    <form method="post" action="{{ route('coach.announcements.update', [$tenant, $announcement]) }}" class="mx-auto max-w-2xl space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="title" class="block text-sm font-medium text-stone-700">Title</label>
            <input id="title" name="title" type="text" value="{{ old('title', $announcement->title) }}" required class="mt-1 w-full rounded-lg border-stone-300">
        </div>
        <div>
            <label for="body" class="block text-sm font-medium text-stone-700">Message</label>
            <textarea id="body" name="body" rows="8" required class="mt-1 w-full rounded-lg border-stone-300">{{ old('body', $announcement->body) }}</textarea>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Update</button>
            <a href="{{ route('coach.announcements.index', $tenant) }}" class="rounded-full px-5 py-2 text-sm text-stone-600 hover:bg-stone-100">Back</a>
        </div>
    </form>
    @if (! $announcement->is_published)
        <form method="post" action="{{ route('coach.announcements.publish', [$tenant, $announcement]) }}" class="mt-3">
            @csrf
            <button type="submit" class="rounded-full border border-teal-200 bg-teal-50 px-5 py-2 text-sm font-medium text-teal-900 hover:bg-teal-100">Publish now</button>
        </form>
    @endif
@endsection
