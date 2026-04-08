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
                @php
                    $m = $memberships->get($tenant->id);
                    $isStaff = $m && in_array($m->role, \App\Enums\TenantRole::staffValues(), true);
                    $snap = $coachSnapshots[$tenant->id] ?? null;
                @endphp
                <li class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm @if ($isStaff) ring-1 ring-teal-100 @endif">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            @if ($isStaff)
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-teal-700">Space you lead</p>
                            @endif
                            <h2 class="font-semibold text-stone-900">{{ $tenant->name }}</h2>
                            <p class="text-xs text-stone-500">{{ $tenant->slug }} @if ($m) · <span class="font-medium text-stone-700">{{ $m->role }}</span> @else · <span class="text-amber-700">Enrolled only — join as member from catalog if needed</span> @endif</p>
                            <p class="mt-2 break-all text-xs text-stone-500">
                                Public link:
                                <a href="{{ route('public.catalog', $tenant) }}" class="font-medium text-teal-700 hover:underline">{{ $tenant->publicUrl('catalog') }}</a>
                            </p>
                        </div>
                    </div>

                    @if ($isStaff && $snap)
                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div class="rounded-xl bg-stone-50 px-3 py-2">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-stone-500">Learners enrolled</p>
                                <p class="text-lg font-semibold text-stone-900">{{ $snap['learners_with_enrollment'] }}</p>
                                <p class="text-[10px] text-stone-500">{{ $snap['active_enrollments'] }} active seats</p>
                            </div>
                            <div class="rounded-xl bg-stone-50 px-3 py-2">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-stone-500">Members (learner role)</p>
                                <p class="text-lg font-semibold text-stone-900">{{ $snap['learner_members'] }}</p>
                            </div>
                            <div class="rounded-xl bg-stone-50 px-3 py-2">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-stone-500">Programs live / draft</p>
                                <p class="text-lg font-semibold text-stone-900">{{ $snap['programs_live'] }} / {{ $snap['programs_draft'] }}</p>
                            </div>
                            <div class="rounded-xl bg-stone-50 px-3 py-2">
                                <p class="text-[10px] font-medium uppercase tracking-wide text-stone-500">Completions (7d)</p>
                                <p class="text-lg font-semibold text-stone-900">{{ $snap['lesson_completions_7d'] }}</p>
                                <p class="text-[10px] text-stone-500">{{ $snap['courses_live'] }} courses · {{ $snap['reflection_prompts_live'] }} live prompts</p>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                        @if ($isStaff)
                            <a href="{{ route('coach.programs.index', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 font-medium text-white hover:bg-teal-700">Programs &amp; courses</a>
                            <a href="{{ route('coach.reflections.index', $tenant) }}" class="rounded-full border border-amber-200 bg-amber-50 px-4 py-2 font-medium text-amber-950 hover:bg-amber-100">Daily reflections</a>
                            <a href="{{ route('public.catalog', $tenant) }}" class="rounded-full border border-stone-300 px-3 py-2 text-stone-800 hover:border-teal-400">Public catalog</a>
                        @endif
                        <a href="{{ route('learn.catalog', $tenant) }}" class="rounded-full border border-stone-300 px-3 py-2 hover:border-teal-400 hover:text-teal-800">Learner catalog</a>
                        <a href="{{ route('learn.continue', $tenant) }}" class="rounded-full bg-stone-900 px-3 py-2 text-white hover:bg-stone-800">Continue</a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <p class="mt-10 text-sm text-stone-600">
        <a href="{{ route('profile') }}" class="font-medium text-teal-800 hover:underline">Profile</a>
        — name, email, and platform role for your account.
    </p>
@endsection
