@extends('layouts.app')

@section('title', $tenant->name.' — catalog')

@section('content')
    <div class="mb-6 rounded-xl border border-teal-100 bg-teal-50/80 px-4 py-3 text-sm text-teal-900">
        Public catalog — no login required to browse.
        @auth
            <a href="{{ route('learn.catalog', $tenant) }}" class="ml-2 font-medium underline">Open in learner mode</a>
        @else
            <a href="{{ route('login') }}" class="ml-2 font-medium underline">Log in</a> to track progress and access enrolled courses.
        @endauth
    </div>

    <h1 class="text-2xl font-semibold tracking-tight">{{ $tenant->name }}</h1>
    <p class="mt-2 text-sm text-stone-600">Published programs and courses.</p>

    @forelse ($programs as $program)
        <section class="mt-10">
            <h2 class="text-lg font-semibold text-stone-900">{{ $program->title }}</h2>
            @if ($program->summary)
                <p class="mt-1 text-sm text-stone-600">{{ $program->summary }}</p>
            @endif
            <ul class="mt-4 space-y-2">
                @foreach ($program->courses as $course)
                    <li class="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm">
                        <span class="font-medium text-stone-900">{{ $course->title }}</span>
                        @if ($course->summary)
                            <p class="mt-0.5 text-sm text-stone-600">{{ $course->summary }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="mt-8 text-stone-600">Nothing published yet.</p>
    @endforelse
@endsection
