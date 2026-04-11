@extends('layouts.app')

@section('title', 'My coaching')

@section('content')
    <section class="mb-10 flex flex-col gap-4 rounded-2xl bg-gradient-to-br from-stone-900 to-teal-950 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-300/90">Coach hub</p>
            <h2 class="mt-1 text-xl font-bold tracking-tight">My coaching</h2>
            <p class="mt-1 max-w-xl text-sm text-stone-300">
                Spaces you lead and quick stats. <a href="{{ route('my-learning') }}" class="font-medium text-white underline decoration-teal-500/70 underline-offset-2 hover:decoration-teal-400">My learning</a> for enrollments.
            </p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:items-end">
            <a href="{{ route('my-learning') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-bold text-stone-900 shadow-sm transition hover:bg-stone-100">
                My learning
            </a>
            <a href="{{ route('spaces.create') }}" class="inline-flex items-center justify-center rounded-full border border-white/40 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                Create another space
            </a>
        </div>
    </section>

    @if (auth()->user()->coachesAnySpace() && $aggregateCoachStats['spaces_led'] > 0)
        <div class="-mx-1 mb-10 flex flex-nowrap gap-3 overflow-x-auto pb-1 sm:mx-0 sm:grid sm:grid-cols-4 sm:overflow-visible sm:px-0" role="list" title="{{ $aggregateCoachStats['programs_live'] }} live programs across spaces you lead">
            <div class="min-w-[42%] shrink-0 rounded-2xl border border-teal-100 bg-teal-50/60 px-3 py-3 shadow-sm sm:min-w-0 sm:shrink" role="listitem">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-800">Spaces you lead</p>
                <p class="text-2xl font-bold tabular-nums text-stone-900">{{ $aggregateCoachStats['spaces_led'] }}</p>
            </div>
            <div class="min-w-[42%] shrink-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm sm:min-w-0 sm:shrink" role="listitem">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Learners (enrolled)</p>
                <p class="text-2xl font-bold tabular-nums text-stone-900">{{ $aggregateCoachStats['learners_with_enrollment'] }}</p>
            </div>
            <div class="min-w-[42%] shrink-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm sm:min-w-0 sm:shrink" role="listitem">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Active enrollments</p>
                <p class="text-2xl font-bold tabular-nums text-stone-900">{{ $aggregateCoachStats['active_enrollments'] }}</p>
            </div>
            <div class="min-w-[42%] shrink-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm sm:min-w-0 sm:shrink" role="listitem">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Lesson completions (7d)</p>
                <p class="text-2xl font-bold tabular-nums text-stone-900">{{ $aggregateCoachStats['lesson_completions_7d'] }}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Spaces you lead</h1>
            <p class="mt-2 text-sm text-stone-600">Programs, reflections, and shareable catalogs per space.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('spaces.create') }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">New space</a>
            <a href="{{ route('my-learning') }}#explore-spaces" class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-800 hover:border-teal-400">Find spaces to join</a>
        </div>
    </div>

    @if ($spacesYouLead->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-teal-200 bg-teal-50/40 p-6 text-sm text-stone-800">
            <p class="font-medium text-stone-900">No spaces you lead yet.</p>
            <p class="mt-2 text-stone-600">Create a space to get programs, a learner catalog, and shareable links.</p>
            <a href="{{ route('spaces.create') }}" class="mt-4 inline-flex rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Create your first space</a>
        </div>
    @else
        <ul class="mt-8 space-y-4">
            @foreach ($spacesYouLead as $tenant)
                @include('my-coaching._space-card', ['tenant' => $tenant, 'memberships' => $memberships, 'coachSnapshots' => $coachSnapshots])
            @endforeach
        </ul>
    @endif

    @if ($spacesYouLearn->isNotEmpty())
        <div class="mt-14">
            <h2 class="text-xl font-semibold tracking-tight text-stone-900">Also learning in</h2>
            <p class="mt-2 text-sm text-stone-600">Learner or member only — no coach tools on these.</p>
            <ul class="mt-6 space-y-4">
                @foreach ($spacesYouLearn as $tenant)
                    @include('my-coaching._space-card', ['tenant' => $tenant, 'memberships' => $memberships, 'coachSnapshots' => $coachSnapshots])
                @endforeach
            </ul>
        </div>
    @endif

    @if ($tenants->isEmpty())
        <div class="mt-8 rounded-2xl border border-stone-200 bg-stone-50/80 p-6 text-sm text-stone-800">
            <p class="font-medium text-stone-900">You’re signed in — add a space or join one to learn.</p>
            <p class="mt-2 text-stone-600"><a href="{{ route('my-learning') }}#explore-spaces" class="font-medium text-teal-800 hover:underline">My learning</a> to find spaces, or create one above.</p>
        </div>
    @endif

@endsection
