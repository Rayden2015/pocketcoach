@extends('layouts.app')

@section('title', 'Search courses')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-stone-900">Search your spaces</h1>
        <p class="mt-1 text-sm text-stone-600">Find published courses in spaces where you have a membership or an active enrollment.</p>
        <form method="GET" action="{{ route('search.courses') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
            <input type="search" name="q" value="{{ $query }}"
                placeholder="Try a course title or topic…"
                autocomplete="off"
                class="min-w-0 flex-1 rounded-xl border border-stone-300 px-4 py-3 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20">
            <button type="submit" class="rounded-xl bg-stone-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-stone-800">
                Search
            </button>
        </form>
    </div>

    @if ($tenantCount === 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-8 text-sm text-amber-950">
            <p>Join a space or enroll in a course first — then you can search courses from those spaces.</p>
            <a href="{{ route('dashboard') }}" class="mt-4 inline-block font-semibold text-teal-800 hover:underline">Go to profile</a>
        </div>
    @elseif (mb_strlen($query) > 0 && mb_strlen($query) < 2)
        <p class="text-sm text-stone-600">Enter at least 2 characters to search.</p>
    @elseif ($query !== '' && $courses->isEmpty())
        <p class="rounded-2xl border border-stone-200 bg-white px-6 py-10 text-center text-sm text-stone-600 shadow-sm">No courses matched “{{ $query }}”. Try another keyword or browse a space catalog.</p>
    @elseif ($query === '')
        <p class="text-sm text-stone-500">Type a keyword and press Search — results stay limited to your spaces for privacy.</p>
    @else
        <ul class="space-y-4">
            @foreach ($courses as $course)
                <li class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $course->tenant->name }}</p>
                            @if ($course->program)
                                <p class="mt-0.5 text-xs text-stone-500">{{ $course->program->title }}</p>
                            @endif
                            <h2 class="mt-1 text-lg font-bold text-stone-900">{{ $course->title }}</h2>
                            @if ($course->summary)
                                <p class="mt-1 line-clamp-2 text-sm text-stone-600">{{ $course->summary }}</p>
                            @endif
                        </div>
                        <a href="{{ route('learn.course', [$course->tenant, $course]) }}"
                            class="inline-flex shrink-0 items-center rounded-full bg-stone-900 px-5 py-2 text-sm font-semibold text-white hover:bg-stone-800">
                            View course
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
