@extends('layouts.app')

@section('title', $tenant->name.' — catalog')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-teal-700">{{ $tenant->name }}</p>
            <h1 class="text-2xl font-semibold tracking-tight">Programs</h1>
        </div>
        <div class="flex gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-stone-600 hover:text-teal-800">Spaces</a>
            <a href="{{ route('learn.continue', $tenant) }}" class="rounded-full bg-teal-600 px-3 py-1.5 text-white hover:bg-teal-700">Continue</a>
        </div>
    </div>

    @forelse ($programs as $program)
        <section class="mb-10">
            <h2 class="text-lg font-semibold text-stone-900">{{ $program->title }}</h2>
            @if ($program->summary)
                <p class="mt-1 text-sm text-stone-600">{{ $program->summary }}</p>
            @endif
            <ul class="mt-4 space-y-2">
                @foreach ($program->courses as $course)
                    <li>
                        <a href="{{ route('learn.course', [$tenant, $course]) }}"
                            class="flex flex-col rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm transition hover:border-teal-300 hover:shadow">
                            <span class="font-medium text-stone-900">{{ $course->title }}</span>
                            @if ($course->summary)
                                <span class="mt-0.5 text-sm text-stone-600">{{ $course->summary }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-stone-600">No published programs yet.</p>
    @endforelse
@endsection
