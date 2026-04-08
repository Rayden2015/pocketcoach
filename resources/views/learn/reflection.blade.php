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
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save reflection</button>
                @if ($responseRow?->first_submitted_at)
                    <p class="text-xs text-stone-500">First submitted {{ $responseRow->first_submitted_at->format('Y-m-d H:i') }}</p>
                @endif
            </form>
        </section>
    </div>
@endsection
