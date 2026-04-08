@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <div class="overflow-hidden rounded-3xl border border-white/60 bg-white/90 px-6 py-10 shadow-[var(--pc-shadow-lg)] backdrop-blur-sm sm:px-10">
        <h1 class="text-3xl font-bold tracking-tight text-[var(--pc-brand)] sm:text-4xl">Coaching, structured.</h1>
        <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
            Find a learning space below, browse what they offer, then register or log in on that space to enroll in courses.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('my-learning') }}" class="pc-btn-primary inline-flex rounded-full px-5 py-2.5 text-sm font-semibold shadow-md">
                    My learning
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-[var(--pc-brand)] shadow-sm hover:border-[var(--pc-accent)]">
                    Profile &amp; spaces
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-[var(--pc-brand)] shadow-sm hover:border-[var(--pc-accent)]">
                    Platform log in
                </a>
            @endauth
            <a href="{{ route('create-space') }}" class="pc-btn-accent inline-flex rounded-full px-5 py-2.5 text-sm font-semibold shadow-md">
                Create a coaching space
            </a>
        </div>
    </div>

    <section class="mt-12">
        <h2 class="text-lg font-bold text-[var(--pc-brand)]">Learning spaces</h2>
        <p class="mt-1 text-sm text-slate-600">Spaces with published programs. Open a catalog to explore courses; use that space’s register or log in link to join and enroll.</p>

        @if ($spaces->isEmpty())
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-6 py-12 text-center text-sm text-slate-600 shadow-sm">
                <p>No public spaces with published programs yet.</p>
                <p class="mt-2">Coaches can <a href="{{ route('create-space') }}" class="font-semibold text-[var(--pc-accent)] hover:underline">create a space</a> and publish a program to appear here.</p>
            </div>
        @else
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($spaces as $space)
                    <li class="flex flex-col rounded-2xl border border-white/70 bg-white/95 p-5 shadow-[var(--pc-shadow)] backdrop-blur-sm transition hover:border-[color-mix(in_srgb,var(--pc-accent)_35%,#e2e8f0)] hover:shadow-[var(--pc-shadow-lg)]">
                        <h3 class="text-base font-bold text-slate-900">{{ $space->name }}</h3>
                        <p class="mt-1 font-mono text-xs text-slate-500">/{{ $space->slug }}</p>
                        <p class="mt-2 text-xs text-slate-600">
                            {{ $space->published_programs_count }} {{ Str::plural('program', $space->published_programs_count) }} published
                        </p>
                        <div class="mt-4 flex flex-1 flex-col gap-2 border-t border-slate-100 pt-4">
                            <a href="{{ route('public.catalog', $space) }}" class="pc-btn-primary inline-flex justify-center rounded-full px-4 py-2.5 text-center text-sm font-semibold shadow-sm">
                                Browse catalog
                            </a>
                            @guest
                                <a href="{{ route('space.register', $space) }}" class="text-center text-sm font-semibold text-[var(--pc-accent)] hover:text-[var(--pc-brand)] hover:underline">
                                    Register on this space
                                </a>
                                <a href="{{ route('space.login', $space) }}" class="text-center text-sm font-medium text-slate-600 hover:text-[var(--pc-brand)] hover:underline">
                                    Log in to this space
                                </a>
                            @else
                                <a href="{{ route('learn.catalog', $space) }}" class="text-center text-sm font-semibold text-[var(--pc-accent)] hover:text-[var(--pc-brand)] hover:underline">
                                    Learner catalog (logged in)
                                </a>
                            @endguest
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <p class="mt-10 text-sm text-slate-500">
        Tip: each space has its own member list. Use <strong class="text-slate-700">Register on this space</strong> or <strong class="text-slate-700">Log in to this space</strong> for learner access; use <strong class="text-slate-700">Platform log in</strong> only for creating spaces or the global dashboard.
    </p>
@endsection
