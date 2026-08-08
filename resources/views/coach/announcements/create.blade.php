@extends('layouts.app')

@section('title', $tenant->name.' — new announcement')

@section('content')
    @include('coach.partials.header')

    <form method="post" action="{{ route('coach.announcements.store', $tenant) }}" class="mx-auto max-w-2xl space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="title" class="block text-sm font-medium text-stone-700">Title</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required class="mt-1 w-full rounded-lg border-stone-300">
        </div>
        <div>
            <label for="body" class="block text-sm font-medium text-stone-700">Message</label>
            <textarea id="body" name="body" rows="8" required class="mt-1 w-full rounded-lg border-stone-300">{{ old('body') }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input type="checkbox" name="publish_now" value="1" checked>
            Publish now and notify members
        </label>
        <div class="flex gap-3">
            <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
            <a href="{{ route('coach.announcements.index', $tenant) }}" class="rounded-full px-5 py-2 text-sm text-stone-600 hover:bg-stone-100">Cancel</a>
        </div>
    </form>
@endsection
