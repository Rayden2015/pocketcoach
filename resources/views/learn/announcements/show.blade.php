@extends('layouts.app')

@section('title', $announcement->title.' — '.$tenant->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('learn.announcements.index', $tenant) }}" class="text-sm text-teal-700 hover:underline">← All announcements</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $announcement->title }}</h1>
        <p class="mt-1 text-sm text-stone-500">{{ $announcement->published_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
    </div>

    <article class="prose prose-stone max-w-none rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        {!! nl2br(e($announcement->body)) !!}
    </article>
@endsection
