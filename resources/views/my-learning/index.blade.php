@extends('layouts.app')

@section('title', 'My learning')

@section('content')
    <div class="mb-10 overflow-hidden rounded-3xl bg-gradient-to-br from-[var(--pc-brand)] via-[color-mix(in_srgb,var(--pc-brand)_88%,#0f172a)] to-[color-mix(in_srgb,var(--pc-accent)_55%,#0f172a)] px-6 py-12 text-white shadow-[var(--pc-shadow-lg)] sm:px-10">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">Your learning hub</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">My learning</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/85">
            Every course you’re enrolled in across your spaces, with clear progress. Pick up where you left off—similar to major learning platforms, with your own brand colors when a space customizes them.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[var(--pc-brand)] shadow-md transition hover:bg-slate-50">
                Browse more spaces
            </a>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-full border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-medium text-white backdrop-blur transition hover:bg-white/20">
                Account &amp; profile
            </a>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300/80 bg-white/90 px-8 py-16 text-center shadow-[var(--pc-shadow)] backdrop-blur-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--pc-accent)] to-[var(--pc-brand)] text-2xl font-bold text-white shadow-lg">
                ∅
            </div>
            <p class="mt-6 text-lg font-bold text-slate-900">You do not have any enrollments yet.</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">
                When a coach enables <strong class="text-[var(--pc-brand)]">free enrollment</strong> on a course, open that course and use <strong>Enroll free</strong>. Your courses will appear here with progress bars automatically.
            </p>
            <a href="{{ route('dashboard') }}"
                class="pc-btn-primary mt-8 inline-flex rounded-full px-6 py-3 text-sm font-semibold shadow-md">
                Go to profile &amp; pick a space
            </a>
        </div>
    @else
        <div class="grid gap-7 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $row)
                @php($tenant = $row['tenant'])
                @php($course = $row['course'])
                @php($initial = strtoupper(\Illuminate\Support\Str::substr($course->title, 0, 1)))
                <article class="group flex flex-col overflow-hidden rounded-3xl border border-white/70 bg-white/95 shadow-[var(--pc-shadow)] backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-[color-mix(in_srgb,var(--pc-accent)_40%,#e2e8f0)] hover:shadow-[var(--pc-shadow-lg)]">
                    <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-[var(--pc-brand)] via-[color-mix(in_srgb,var(--pc-brand)_70%,var(--pc-accent))] to-[var(--pc-accent)]">
                        <div class="absolute inset-0 flex items-center justify-center text-6xl font-black text-white/20 transition group-hover:scale-105 group-hover:text-white/30">
                            {{ $initial }}
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/75 via-black/35 to-transparent px-4 pb-4 pt-16">
                            <span class="inline-flex rounded-lg bg-white/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-white/25 backdrop-blur-sm">
                                {{ $tenant->name }}
                            </span>
                            <h2 class="mt-2 line-clamp-2 text-base font-bold leading-snug text-white drop-shadow-sm">
                                {{ $course->title }}
                            </h2>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-5 sm:p-6">
                        @if ($course->program)
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--pc-accent)]">{{ $course->program->title }}</p>
                        @endif
                        @if ($course->summary)
                            <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $course->summary }}</p>
                        @endif

                        <div class="mt-5 flex-1">
                            @if ($row['lessons_total'] === 0)
                                <p class="text-xs text-slate-500">No published lessons in this course yet.</p>
                            @else
                                <div class="flex items-center justify-between text-xs font-medium text-slate-600">
                                    <span>Your progress</span>
                                    <span class="text-sm font-bold text-[var(--pc-brand)]">{{ $row['percent'] }}%</span>
                                </div>
                                <div class="pc-progress-track mt-3 h-3 overflow-hidden rounded-full">
                                    <div class="pc-progress-fill h-full rounded-full transition-all duration-700 ease-out shadow-sm"
                                        style="width: {{ $row['percent'] }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $row['lessons_completed'] }} of {{ $row['lessons_total'] }} lessons completed
                                    @if ($row['is_complete'])
                                        <span class="font-semibold text-[var(--pc-accent)]"> · Done</span>
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div class="mt-6 flex flex-col gap-2 border-t border-slate-100 pt-5">
                            <a href="{{ $row['continue_url'] }}"
                                class="pc-btn-primary inline-flex w-full items-center justify-center rounded-full px-4 py-3 text-center text-sm font-semibold shadow-md">
                                {{ $row['is_complete'] ? 'Review course' : 'Continue learning' }}
                            </a>
                            <a href="{{ route('learn.catalog', $tenant) }}"
                                class="text-center text-xs font-semibold text-[var(--pc-accent)] hover:text-[var(--pc-brand)] hover:underline">
                                More from {{ $tenant->name }} →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
