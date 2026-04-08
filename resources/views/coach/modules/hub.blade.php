@extends('layouts.app')

@section('title', 'Modules — pick a course')

@section('content')
    @include('coach.partials.header')

    <p class="mb-6 max-w-2xl text-sm text-stone-600">
        Modules belong to a <strong>course</strong>. Pick a course below to list or create modules. Short courses can skip modules and use
        <a href="{{ route('coach.lessons.index', $tenant) }}" class="font-medium text-teal-800 hover:underline">course-level lessons</a> instead.
    </p>

    @forelse ($programs as $program)
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-stone-900">{{ $program->title }}</h2>
            @if ($program->summary)
                <p class="mt-1 text-sm text-stone-600">{{ $program->summary }}</p>
            @endif
            <ul class="mt-3 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
                @foreach ($program->courses as $course)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <span class="font-medium text-stone-900">{{ $course->title }}</span>
                        <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}"
                            class="text-sm font-medium text-teal-700 hover:underline">Open modules</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        @if ($standaloneCourses->isEmpty())
            <p class="text-sm text-stone-600">No courses yet. Create a <a href="{{ route('coach.programs.index', $tenant) }}" class="text-teal-800 hover:underline">program</a> or a <a href="{{ route('coach.courses.standalone.create', $tenant) }}" class="text-teal-800 hover:underline">standalone course</a>.</p>
        @endif
    @endforelse

    @if ($standaloneCourses->isNotEmpty())
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-stone-900">Single courses</h2>
            <p class="mt-1 text-sm text-stone-600">Not in a program — you can still add modules if you want sections.</p>
            <ul class="mt-3 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
                @foreach ($standaloneCourses as $course)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <span class="font-medium text-stone-900">{{ $course->title }}</span>
                        <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}"
                            class="text-sm font-medium text-teal-700 hover:underline">Open modules</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
