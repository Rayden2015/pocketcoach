@extends('layouts.app')

@section('title', 'New lesson')

@section('content')
    @include('coach.partials.header')

    @if ($module ?? null)
        <div class="mb-4 text-sm">
            <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id]) }}" class="text-teal-700 hover:underline">← Lessons</a>
        </div>

        <h2 class="text-lg font-semibold">New lesson in {{ $module->title }}</h2>

        <form method="POST" action="{{ route('coach.lessons.store', $tenant) }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="module_id" value="{{ $module->id }}">
            @include('coach.partials.lesson-fields', ['lesson' => null])
            <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Create</button>
        </form>
    @else
        <div class="mb-4 text-sm">
            <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'course_id' => $courseForRoot->id]) }}" class="text-teal-700 hover:underline">← Course-level lessons</a>
        </div>

        <h2 class="text-lg font-semibold">New course-level lesson in {{ $courseForRoot->title }}</h2>
        <p class="mt-1 text-sm text-stone-600">This lesson sits directly on the course (no module). Use a module if you need sections.</p>

        <form method="POST" action="{{ route('coach.lessons.store', $tenant) }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="course_id" value="{{ $courseForRoot->id }}">
            @include('coach.partials.lesson-fields', ['lesson' => null])
            <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Create</button>
        </form>
    @endif
@endsection
