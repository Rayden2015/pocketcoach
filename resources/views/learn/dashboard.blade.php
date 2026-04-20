@extends('layouts.app')

@section('title', $tenant->name.' — Home')

@section('content')
    @php($m = $dashboard['membership'] ?? null)
    @php($learner = $dashboard['learner'] ?? [])
    @php($coach = $dashboard['coach'] ?? null)

    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-teal-700">{{ $tenant->name }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-stone-900">Your space home</h1>
            <p class="mt-2 max-w-2xl text-sm text-stone-600">Progress, quick actions, and — for coaches — tools for this space.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('learn.catalog', $tenant) }}" class="rounded-full border border-stone-300 bg-white px-4 py-2 font-medium text-stone-800 hover:border-teal-400">Catalog</a>
            <a href="{{ route('public.book', $tenant) }}" class="rounded-full border border-teal-200 bg-teal-50 px-4 py-2 font-medium text-teal-900 hover:bg-teal-100" title="Open times are set by your coach.">Book a session</a>
            <a href="{{ route('learn.continue', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 font-medium text-white hover:bg-teal-700">Continue</a>
        </div>
    </div>

    @if ($m && ($m['is_staff'] ?? false))
        <div class="mb-8 rounded-2xl border border-amber-200/90 bg-gradient-to-br from-amber-50 to-white px-5 py-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-900/80">Coach</p>
                    <p class="mt-1 text-sm text-stone-700">You’re <strong>{{ ucfirst($m['role']) }}</strong> in this space — open the coach console for programs, courses, reflections, and submissions.</p>
                </div>
                <a href="{{ route('coach.home', $tenant) }}" class="shrink-0 rounded-full bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">Coach console</a>
            </div>
            @if ($coach)
                <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-stone-200 bg-white/90 px-3 py-3">
                        <dt class="text-[10px] font-semibold uppercase text-stone-500">Live programs</dt>
                        <dd class="text-2xl font-bold tabular-nums text-stone-900">{{ $coach['programs_live'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-white/90 px-3 py-3">
                        <dt class="text-[10px] font-semibold uppercase text-stone-500">Live courses</dt>
                        <dd class="text-2xl font-bold tabular-nums text-stone-900">{{ $coach['courses_live'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-white/90 px-3 py-3">
                        <dt class="text-[10px] font-semibold uppercase text-stone-500">Active enrollments</dt>
                        <dd class="text-2xl font-bold tabular-nums text-stone-900">{{ $coach['active_enrollments'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-white/90 px-3 py-3">
                        <dt class="text-[10px] font-semibold uppercase text-stone-500">Lesson completions (7d)</dt>
                        <dd class="text-2xl font-bold tabular-nums text-stone-900">{{ $coach['lesson_completions_7d'] }}</dd>
                    </div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('coach.programs.index', $tenant) }}" class="rounded-full border border-stone-200 bg-white px-3 py-1.5 font-medium text-teal-800 hover:bg-stone-50">Programs</a>
                    <a href="{{ route('coach.courses.index', $tenant) }}" class="rounded-full border border-stone-200 bg-white px-3 py-1.5 font-medium text-teal-800 hover:bg-stone-50">Courses</a>
                    <a href="{{ route('coach.reflections.index', $tenant) }}" class="rounded-full border border-stone-200 bg-white px-3 py-1.5 font-medium text-teal-800 hover:bg-stone-50">Reflections</a>
                    <a href="{{ route('coach.learner-submissions.index', $tenant) }}" class="rounded-full border border-stone-200 bg-white px-3 py-1.5 font-medium text-teal-800 hover:bg-stone-50">Learner submissions</a>
                    <a href="{{ route('coach.bookings.index', $tenant) }}" class="rounded-full border border-stone-200 bg-white px-3 py-1.5 font-medium text-teal-800 hover:bg-stone-50">Bookings</a>
                </div>
                @if (($coach['scheduled_reflections_pending'] ?? 0) > 0)
                    <p class="mt-3 text-xs text-amber-900/90">
                        <strong>{{ $coach['scheduled_reflections_pending'] }}</strong> reflection(s) scheduled to publish — <a href="{{ route('coach.reflections.index', $tenant) }}" class="font-semibold underline">manage prompts</a>
                    </p>
                @endif
            @endif
        </div>
    @endif

    <h2 class="text-lg font-semibold text-stone-900">Your learning</h2>
    <p class="mt-1 text-sm text-stone-600">Activity in this space (enrolled courses only).</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Lessons done (7 days)</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-stone-900">{{ $learner['lessons_completed_7d'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Lessons done (30 days)</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-stone-900">{{ $learner['lessons_completed_30d'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Courses completed</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-teal-700">{{ $learner['courses_completed'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">In progress</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-stone-900">{{ $learner['courses_in_progress'] ?? 0 }}</p>
        </div>
    </div>

    @if (($learner['courses_not_started'] ?? 0) > 0 || ($learner['courses_enrolled'] ?? 0) === 0)
        <p class="mt-3 text-sm text-stone-600">
            @if (($learner['courses_enrolled'] ?? 0) === 0)
                You’re not enrolled in any courses in this space yet — browse the <a href="{{ route('learn.catalog', $tenant) }}" class="font-medium text-teal-700 hover:underline">catalog</a> and enroll when a coach enables access.
            @else
                <span class="tabular-nums">{{ $learner['courses_not_started'] }}</span> enrolled course(s) not started yet.
            @endif
        </p>
    @endif

    @if (!empty($learner['continue']))
        <div class="mt-8 rounded-2xl border border-teal-200 bg-teal-50/50 p-6 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-teal-900">Continue where you left off</h3>
            <p class="mt-2 text-stone-800">
                <span class="font-medium text-teal-800">{{ $learner['continue']['course']['title'] ?? '' }}</span>
                <span class="text-stone-500"> — </span>
                {{ $learner['continue']['lesson']['title'] ?? '' }}
            </p>
            <a href="{{ route('learn.lesson', [$tenant, $learner['continue']['lesson']['id'] ?? 0]) }}" class="mt-4 inline-flex rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Open lesson</a>
        </div>
    @else
        <div class="mt-8 rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-5 py-8 text-center text-sm text-stone-600">
            No lesson to continue right now — you’re caught up or not enrolled. Try <a href="{{ route('learn.catalog', $tenant) }}" class="font-medium text-teal-700 hover:underline">the catalog</a>.
        </div>
    @endif

    <div class="mt-10 flex flex-wrap gap-3 text-sm">
        <a href="{{ route('my-learning') }}" class="text-teal-700 hover:underline">All my learning (all spaces)</a>
        @if (auth()->user()->coachesAnySpace())
            <span class="text-stone-300">·</span>
            <a href="{{ route('my-coaching') }}" class="text-teal-700 hover:underline">My coaching</a>
        @endif
    </div>
@endsection
