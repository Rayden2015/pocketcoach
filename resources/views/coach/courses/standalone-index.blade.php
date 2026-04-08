@extends('layouts.app')

@section('title', 'Single courses')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-sm text-stone-600">These courses are <strong>not</strong> inside a program. Use them for one-off offers or quick curricula.</p>
        <a href="{{ route('coach.courses.standalone.create', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New single course</a>
    </div>

    <ul class="divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($courses as $course)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <span class="font-medium text-stone-900">{{ $course->title }}</span>
                    <span class="ml-2 text-xs text-stone-500">{{ $course->slug }}</span>
                    <span class="ml-2 text-xs {{ $course->is_published ? 'text-teal-700' : 'text-stone-400' }}">{{ $course->is_published ? 'Published' : 'Draft' }}</span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}" class="text-teal-700 hover:underline">Modules</a>
                    <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}" class="text-teal-700 hover:underline">Course lessons</a>
                    <a href="{{ route('coach.courses.edit', [$tenant, $course]) }}" class="text-teal-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('coach.courses.destroy', [$tenant, $course]) }}" class="inline" onsubmit="return confirm('Delete this course and nested modules?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No standalone courses yet.</li>
        @endforelse
    </ul>
@endsection
