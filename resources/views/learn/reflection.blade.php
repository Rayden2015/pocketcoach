@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', ($prompt->title ?: 'Reflection').' — '.$tenant->name)

@section('content')
    <div class="mb-4 text-sm">
        <a href="{{ route('public.catalog', $tenant) }}" class="text-teal-700 hover:underline">← Catalog</a>
        <span class="mx-2 text-stone-300">|</span>
        <a href="{{ route('learn.catalog', $tenant) }}" class="text-teal-700 hover:underline">Learner home</a>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ session('status') }}</p>
    @endif

    <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-teal-700">Reflection prompt</p>
        @if ($prompt->title)
            <h1 class="mt-2 text-2xl font-semibold text-stone-900">{{ $prompt->title }}</h1>
        @endif
        <div class="prose prose-stone prose-sm mt-4 max-w-none">
            {!! Str::markdown($prompt->body) !!}
        </div>
    </article>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <section class="rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm">
            <h2 class="font-semibold text-stone-900">Your view log</h2>
            @if ($viewRow)
                <p class="mt-2 text-stone-700">First opened: <time datetime="{{ $viewRow->first_viewed_at?->toIso8601String() }}">{{ $viewRow->first_viewed_at?->format('Y-m-d H:i') }}</time></p>
                <p class="text-stone-700">Last opened: <time datetime="{{ $viewRow->last_viewed_at?->toIso8601String() }}">{{ $viewRow->last_viewed_at?->format('Y-m-d H:i') }}</time></p>
            @else
                <p class="mt-2 text-stone-600">Open this page to record your first view.</p>
            @endif
        </section>
        <section class="rounded-xl border border-stone-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-stone-900">Your reflection</h2>
            <form method="POST" action="{{ route('learn.reflections.response', [$tenant, $prompt]) }}" class="mt-3 space-y-3">
                @csrf
                <textarea name="body" rows="6" required
                    class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    placeholder="Write your reflection…">{{ old('body', $responseRow->body ?? '') }}</textarea>
                @error('body')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                    <input type="checkbox" name="is_public" value="1" class="mt-1 rounded border-stone-300 text-teal-600 focus:ring-teal-500"
                        @checked(old('is_public', $responseRow->is_public ?? false))>
                    <span><span class="font-medium">Share with other learners</span> — visible to enrolled members on this reflection page.</span>
                </label>
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save reflection</button>
                @if ($responseRow?->first_submitted_at)
                    <p class="text-xs text-stone-500">First submitted {{ $responseRow->first_submitted_at->format('Y-m-d H:i') }}</p>
                @endif
            </form>
        </section>
    </div>

    @if ($responseRow)
        <section class="mt-8 rounded-2xl border border-amber-200/80 bg-amber-50/40 p-6 shadow-sm">
            <h2 class="text-sm font-bold text-stone-900">Coach conversation</h2>
            <p class="mt-1 text-xs text-stone-600">Discuss this reflection with your coach. Only you and coaches in this space can see this thread.</p>
            <a href="{{ route('submission-conversations.reflection.show', [$tenant, $responseRow]) }}"
                class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-900">
                Open conversation
                @if (($responseRow->conversation_messages_count ?? 0) > 0)
                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-medium">{{ $responseRow->conversation_messages_count }} messages</span>
                @endif
            </a>
        </section>
    @else
        <p class="mt-6 text-sm text-stone-600">Save your reflection above to start a private conversation with your coach.</p>
    @endif

    @if ($publicPeerResponses->isNotEmpty())
        <section class="mt-8 rounded-2xl border border-teal-100 bg-teal-50/40 p-6 shadow-sm">
            <h2 class="text-sm font-bold text-stone-900">From other learners</h2>
            <p class="mt-1 text-xs text-stone-600">Reflections they chose to share.</p>
            <ul class="mt-4 space-y-4">
                @foreach ($publicPeerResponses as $peer)
                    <li class="rounded-xl border border-teal-100/80 bg-white p-4 shadow-sm">
                        <p class="text-sm font-medium text-stone-900">{{ $peer->user?->name ?? 'Learner' }}</p>
                        <div class="prose prose-sm prose-stone mt-2 max-w-none text-stone-800">
                            {!! Str::markdown($peer->body ?? '') !!}
                        </div>
                        <p class="mt-2 text-xs text-stone-500">Updated {{ $peer->updated_at?->format('Y-m-d H:i') }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
