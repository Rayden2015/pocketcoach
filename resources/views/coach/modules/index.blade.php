@extends('layouts.app')

@section('title', $course->title.' — modules')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.programs.index', $tenant) }}" class="text-teal-700 hover:underline">Programs</a>
        <span class="text-stone-400">/</span>
        <a href="{{ route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $course->program_id]) }}" class="text-teal-700 hover:underline">{{ $course->program->title }}</a>
        <span class="text-stone-400">/</span>
        <span class="text-stone-800">{{ $course->title }}</span>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">Modules</h2>
        <a href="{{ route('coach.modules.create', ['tenant' => $tenant, 'course_id' => $course->id]) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New module</a>
    </div>

    <ul class="divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($modules as $module)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id]) }}" class="font-medium text-stone-900 hover:text-teal-800">{{ $module->title }}</a>
                    <span class="ml-2 text-xs text-stone-500">{{ $module->slug }}</span>
                    <span class="ml-2 text-xs {{ $module->is_published ? 'text-teal-700' : 'text-stone-400' }}">{{ $module->is_published ? 'Published' : 'Draft' }}</span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('coach.modules.edit', [$tenant, $module]) }}" class="text-teal-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('coach.modules.destroy', [$tenant, $module]) }}" class="inline" onsubmit="return confirm('Delete module and lessons?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No modules yet.</li>
        @endforelse
    </ul>
@endsection
