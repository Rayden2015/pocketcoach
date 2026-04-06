@extends('layouts.app')

@section('title', $module->title.' — lessons')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $module->course_id]) }}" class="text-teal-700 hover:underline">← Modules</a>
        <span class="text-stone-400">/</span>
        <span class="text-stone-800">{{ $module->title }}</span>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">Lessons</h2>
        <a href="{{ route('coach.lessons.create', ['tenant' => $tenant, 'module_id' => $module->id]) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New lesson</a>
    </div>

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
