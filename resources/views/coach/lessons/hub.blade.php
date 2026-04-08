@extends('layouts.app')

@section('title', 'Lessons — pick a module or course')

@section('content')
    @include('coach.partials.header')

    <p class="mb-6 max-w-2xl text-sm text-stone-600">
        Lessons live either <strong>inside a module</strong> or <strong>directly on a course</strong> (no module). Open the list you want to edit.
    </p>

    @foreach ($programs as $program)
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-stone-900">{{ $program->title }}</h2>
            <ul class="mt-3 space-y-4">
                @foreach ($program->courses as $course)
                    <li class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-stone-900">{{ $course->title }}</span>
                            <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}"
                                class="text-sm font-medium text-teal-700 hover:underline">Course-level lessons</a>
                        </div>
                        @if ($course->modules->isNotEmpty())
                            <ul class="mt-3 border-t border-stone-100 pt-3 text-sm">
                                @foreach ($course->modules as $module)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-1">
                                        <span class="text-stone-700">{{ $module->title }}</span>
                                        <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id]) }}"
                                            class="text-teal-700 hover:underline">Lessons in module</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @if ($standaloneCourses->isNotEmpty())
        <section class="mb-8">
            <h2 class="text-lg font-semibold text-stone-900">Single courses</h2>
            <ul class="mt-3 space-y-4">
                @foreach ($standaloneCourses as $course)
                    <li class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-stone-900">{{ $course->title }}</span>
                            <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}"
                                class="text-sm font-medium text-teal-700 hover:underline">Course-level lessons</a>
                        </div>
                        @if ($course->modules->isNotEmpty())
                            <ul class="mt-3 border-t border-stone-100 pt-3 text-sm">
                                @foreach ($course->modules as $module)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-1">
                                        <span class="text-stone-700">{{ $module->title }}</span>
                                        <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id]) }}"
                                            class="text-teal-700 hover:underline">Lessons in module</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($programs->isEmpty() && $standaloneCourses->isEmpty())
        <p class="text-sm text-stone-600">No courses yet. Start from <a href="{{ route('coach.programs.index', $tenant) }}" class="text-teal-800 hover:underline">Programs</a> or create a <a href="{{ route('coach.courses.standalone.create', $tenant) }}" class="text-teal-800 hover:underline">standalone course</a>.</p>
    @endif
@endsection
