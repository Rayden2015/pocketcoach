@extends('layouts.app')

@section('title', $tenant->name.' — catalog')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-teal-700">{{ $tenant->name }}</p>
            <h1 class="text-2xl font-semibold tracking-tight">Catalog</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('my-learning') }}" class="text-stone-600 hover:text-teal-800">My learning</a>
            <a href="{{ route('profile') }}" class="text-stone-600 hover:text-teal-800">Profile</a>
            <a href="{{ route('learn.continue', $tenant) }}" class="rounded-full bg-teal-600 px-3 py-1.5 text-white hover:bg-teal-700">Continue</a>
        </div>
    </div>

    @auth
        @php($catalogMember = auth()->user()->memberships()->where('tenant_id', $tenant->id)->exists())
        @if (! $catalogMember)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-800">
                <p>You have a login but are not a member of this space yet. Join to show up as a learner and use learner tools.</p>
                <form method="POST" action="{{ route('space.join', $tenant) }}">
                    @csrf
                    <button type="submit" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Join this space</button>
                </form>
            </div>
        @endif
    @endauth

    <div class="mb-8 rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700 shadow-sm">
        <p><strong>Enrolling in a course:</strong> open a course below, then use <strong>Enroll free</strong> on the course page when the coach has added a free enrollment offer for that course or program. If you do not see that button, ask your coach to enable free access or to enroll you manually.</p>
        <p class="mt-2 text-xs text-stone-500">Programs group courses. Some courses may sit <strong>outside any program</strong>. Enrollment is at the <strong>course</strong> level (or whole-program when a product is scoped to the program).</p>
    </div>

    @foreach ($programs as $program)
        <section class="mb-10">
            <h2 class="text-lg font-semibold text-stone-900">{{ $program->title }}</h2>
            @if ($program->summary)
                <p class="mt-1 text-sm text-stone-600">{{ $program->summary }}</p>
            @endif
            <ul class="mt-4 space-y-2">
                @foreach ($program->courses as $course)
                    @php($meta = $courseMeta[$course->id] ?? ['is_enrolled' => false, 'free_product_id' => null])
                    <li>
                        <a href="{{ route('learn.course', [$tenant, $course]) }}"
                            class="flex flex-col rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm transition hover:border-teal-300 hover:shadow">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-stone-900">{{ $course->title }}</span>
                                @if ($meta['is_enrolled'])
                                    <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-900">Enrolled</span>
                                @elseif ($meta['free_product_id'])
                                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-700">Free enrollment</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-900">Access on request</span>
                                @endif
                            </span>
                            @if ($course->summary)
                                <span class="mt-0.5 text-sm text-stone-600">{{ $course->summary }}</span>
                            @endif
                            <span class="mt-1 text-xs text-stone-500">Open the course page to enroll or start lessons.</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @if ($standaloneCourses->isNotEmpty())
        <section class="mb-10">
            <h2 class="text-lg font-semibold text-stone-900">Single courses</h2>
            <p class="mt-1 text-sm text-stone-600">Not part of a program — open directly below.</p>
            <ul class="mt-4 space-y-2">
                @foreach ($standaloneCourses as $course)
                    @php($meta = $courseMeta[$course->id] ?? ['is_enrolled' => false, 'free_product_id' => null])
                    <li>
                        <a href="{{ route('learn.course', [$tenant, $course]) }}"
                            class="flex flex-col rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm transition hover:border-teal-300 hover:shadow">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-stone-900">{{ $course->title }}</span>
                                @if ($meta['is_enrolled'])
                                    <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-900">Enrolled</span>
                                @elseif ($meta['free_product_id'])
                                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-700">Free enrollment</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-900">Access on request</span>
                                @endif
                            </span>
                            @if ($course->summary)
                                <span class="mt-0.5 text-sm text-stone-600">{{ $course->summary }}</span>
                            @endif
                            <span class="mt-1 text-xs text-stone-500">Open the course page to enroll or start lessons.</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($programs->isEmpty() && $standaloneCourses->isEmpty())
        <p class="text-stone-600">No published programs or courses yet.</p>
    @endif
@endsection
