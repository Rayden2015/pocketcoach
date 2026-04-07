@extends('layouts.app')

@section('title', 'My learning')

@section('content')
    {{-- Udemy-style hub: course grid, progress, single primary CTA per card --}}
    <div class="mb-10 rounded-2xl bg-gradient-to-br from-stone-900 via-stone-800 to-teal-950 px-6 py-10 text-white shadow-xl sm:px-10">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-300/90">Your courses</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">My learning</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-stone-300">
            Everything you’re enrolled in, across all spaces—similar to what you see on Udemy or Coursera. Open any course to continue exactly where you left off.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-stone-900 shadow-sm transition hover:bg-stone-100">
                Browse more spaces
            </a>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-full border border-white/25 bg-white/5 px-5 py-2.5 text-sm font-medium text-white backdrop-blur transition hover:bg-white/10">
                Profile &amp; account
            </a>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-8 py-16 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-stone-700 text-2xl font-bold text-white">
                ∅
            </div>
            <p class="mt-6 text-lg font-semibold text-stone-900">You do not have any enrollments yet.</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-stone-600">
                When a coach enables <strong>free enrollment</strong> on a course, open that course and tap <strong>Enroll free</strong>. Your courses in progress will show up here automatically.
            </p>
            <a href="{{ route('dashboard') }}"
                class="mt-8 inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800">
                Go to profile &amp; pick a space
            </a>
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $row)
                @php($tenant = $row['tenant'])
                @php($course = $row['course'])
                @php($initial = strtoupper(\Illuminate\Support\Str::substr($course->title, 0, 1)))
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-sm transition hover:border-teal-300/60 hover:shadow-lg">
                    <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-teal-600 via-stone-700 to-stone-900">
                        <div class="absolute inset-0 flex items-center justify-center text-5xl font-bold text-white/25 transition group-hover:scale-105 group-hover:text-white/35">
                            {{ $initial }}
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent px-4 pb-3 pt-12">
                            <span class="inline-flex rounded-md bg-white/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur-sm">
                                {{ $tenant->name }}
                            </span>
                            <h2 class="mt-1 line-clamp-2 text-base font-bold leading-snug text-white">
                                {{ $course->title }}
                            </h2>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        @if ($course->program)
                            <p class="text-xs font-medium text-stone-500">{{ $course->program->title }}</p>
                        @endif
                        @if ($course->summary)
                            <p class="mt-1 line-clamp-2 text-sm text-stone-600">{{ $course->summary }}</p>
                        @endif

                        <div class="mt-4 flex-1">
                            @if ($row['lessons_total'] === 0)
                                <p class="text-xs text-stone-500">No published lessons in this course yet.</p>
                            @else
                                <div class="flex items-center justify-between text-xs text-stone-600">
                                    <span>Your progress</span>
                                    <span class="font-semibold text-stone-900">{{ $row['percent'] }}%</span>
                                </div>
                                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-stone-200">
                                    <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-teal-600 transition-all duration-500"
                                        style="width: {{ $row['percent'] }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-stone-500">
                                    {{ $row['lessons_completed'] }} of {{ $row['lessons_total'] }} lessons
                                    @if ($row['is_complete'])
                                        <span class="font-medium text-teal-700">· Completed</span>
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-col gap-2 border-t border-stone-100 pt-4">
                            <a href="{{ $row['continue_url'] }}"
                                class="inline-flex w-full items-center justify-center rounded-full bg-stone-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-stone-800">
                                {{ $row['is_complete'] ? 'Review course' : 'Continue learning' }}
                            </a>
                            <a href="{{ route('learn.catalog', $tenant) }}"
                                class="text-center text-xs font-medium text-teal-700 hover:text-teal-900 hover:underline">
                                More from {{ $tenant->name }}
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
