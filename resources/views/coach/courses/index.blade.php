@extends('layouts.app')

@section('title', $program->title.' — courses')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.programs.index', $tenant) }}" class="text-teal-700 hover:underline">← Programs</a>
        <span class="text-stone-400">/</span>
        <span class="text-stone-800">{{ $program->title }}</span>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">Courses</h2>
        <a href="{{ route('coach.courses.create', ['tenant' => $tenant, 'program_id' => $program->id]) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New course</a>
    </div>

    <ul class="divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($courses as $course)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}" class="font-medium text-stone-900 hover:text-teal-800">{{ $course->title }}</a>
                    <span class="ml-2 text-xs text-stone-500">{{ $course->slug }}</span>
                    <span class="ml-2 text-xs {{ $course->is_published ? 'text-teal-700' : 'text-stone-400' }}">{{ $course->is_published ? 'Published' : 'Draft' }}</span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('coach.courses.edit', [$tenant, $course]) }}" class="text-teal-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('coach.courses.destroy', [$tenant, $course]) }}" class="inline" onsubmit="return confirm('Delete this course and nested modules?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No courses yet.</li>
        @endforelse
    </ul>
@endsection
