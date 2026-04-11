@extends('layouts.app')

@section('title', 'Your spaces')

@section('content')
    <section class="mb-10 flex flex-col gap-4 rounded-2xl bg-gradient-to-br from-stone-900 to-teal-950 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-300/90">Your hub</p>
            <h2 class="mt-1 text-xl font-bold tracking-tight">
                @if (! empty($coachSnapshots))
                    Coaching &amp; learning in one place
                @else
                    Pick up where you left off
                @endif
            </h2>
            <p class="mt-1 max-w-xl text-sm text-stone-300">
                @if (! empty($coachSnapshots))
                    <strong>Pocket Coach:</strong> run programs and daily reflections under <em>Spaces you lead</em>, then open <strong>My learning</strong> anytime you’re taking a course as a learner.
                @else
                    Open a space below, or use <strong>My learning</strong> for every course you’re enrolled in.
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:items-end">
            <a href="{{ route('my-learning') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-bold text-stone-900 shadow-sm transition hover:bg-stone-100">
                My learning
            </a>
            @if (! empty($coachSnapshots))
                <p class="text-center text-[11px] text-teal-200/90 sm:text-right">Udemy-style catalog is there too — your edge is rituals, prompts, and space identity.</p>
            @endif
        </div>
    </section>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Your spaces</h1>
            <p class="mt-2 text-sm text-stone-600">
                @if (! empty($coachSnapshots))
                    <strong>Spaces you lead</strong> show coach shortcuts and a quick activity snapshot. Other spaces are where you’re learning.
                @else
                    Spaces you’re a member of or have enrollments in.
                @endif
            </p>
        </div>
        <a href="{{ route('my-learning') }}#explore-spaces" class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-800 hover:border-teal-400">Find courses to join</a>
    </div>

    @if ($tenants->isEmpty())
        <div class="mt-8 rounded-2xl border border-stone-200 bg-stone-50/80 p-6 text-sm text-stone-800">
            <p class="font-medium text-stone-900">You’re signed in and ready — join a space when you’re ready to learn.</p>
            <p class="mt-2 text-stone-600">Open <a href="{{ route('my-learning') }}#explore-spaces" class="font-medium text-teal-800 hover:underline">My learning</a> to browse spaces you can join, then use <strong>Join this space</strong> on a learner catalog when you’re ready.</p>
        </div>
    @else
        <ul class="mt-8 space-y-4">
            @foreach ($tenants as $tenant)
                @include('my-coaching._space-card', ['tenant' => $tenant, 'memberships' => $memberships, 'coachSnapshots' => $coachSnapshots ?? []])
            @endforeach
        </ul>
    @endif

    <p class="mt-10 text-sm text-stone-600">
        <a href="{{ route('profile') }}" class="font-medium text-teal-800 hover:underline">Profile</a>
        — name, email, and platform role for your account.
    </p>
@endsection
