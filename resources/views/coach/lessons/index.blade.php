@extends('layouts.app')

@section('title', ($listContext === 'module' ? $module->title : $courseForRoot->title).' — lessons')

@section('content')
    @include('coach.partials.header')

    @if ($listContext === 'module')
        <div class="mb-4 text-sm">
            <a href="{{ route('coach.lessons.index', $tenant) }}" class="text-teal-700 hover:underline">All lessons</a>
            <span class="text-stone-400">/</span>
            <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $module->course_id]) }}" class="text-teal-700 hover:underline">Modules</a>
            <span class="text-stone-400">/</span>
            <span class="text-stone-800">{{ $module->title }}</span>
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Lessons</h2>
            <a href="{{ route('coach.lessons.create', ['tenant' => $tenant, 'module_id' => $module->id]) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New lesson</a>
        </div>
    @else
        <div class="mb-4 text-sm">
            <a href="{{ route('coach.lessons.index', $tenant) }}" class="text-teal-700 hover:underline">All lessons</a>
            <span class="text-stone-400">/</span>
            @if ($courseForRoot->program_id && $courseForRoot->program)
                <a href="{{ route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $courseForRoot->program_id]) }}" class="text-teal-700 hover:underline">{{ $courseForRoot->program->title }}</a>
                <span class="text-stone-400">/</span>
            @else
                <a href="{{ route('coach.courses.standalone.index', $tenant) }}" class="text-teal-700 hover:underline">Single courses</a>
                <span class="text-stone-400">/</span>
            @endif
            <span class="text-stone-800">{{ $courseForRoot->title }}</span>
            <span class="text-stone-400">/</span>
            <span class="text-stone-600">Course-level</span>
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Course-level lessons</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $courseForRoot->id]) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-medium text-stone-800 hover:bg-stone-50">Modules</a>
                <a href="{{ route('coach.lessons.create', ['tenant' => $tenant, 'course_id' => $courseForRoot->id]) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New lesson</a>
            </div>
        </div>
        <p class="mb-4 text-sm text-stone-600">These lessons are not inside a module. Learners see them first in the course outline.</p>
    @endif

    <ul class="divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($lessons as $lesson)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <span class="font-medium text-stone-900">{{ $lesson->title }}</span>
                    <span class="ml-2 text-xs text-stone-500">{{ $lesson->slug }} · {{ $lesson->lesson_type }}</span>
                    <span class="ml-2 text-xs {{ $lesson->is_published ? 'text-teal-700' : 'text-stone-400' }}">{{ $lesson->is_published ? 'Published' : 'Draft' }}</span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('coach.lessons.edit', [$tenant, $lesson]) }}" class="text-teal-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('coach.lessons.destroy', [$tenant, $lesson]) }}" class="inline" onsubmit="return confirm('Delete this lesson?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No lessons yet.</li>
        @endforelse
    </ul>
@endsection
