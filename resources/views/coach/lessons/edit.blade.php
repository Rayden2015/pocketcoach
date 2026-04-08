@extends('layouts.app')

@section('title', 'Edit lesson')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        @if ($lesson->module_id)
            <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $lesson->module_id]) }}" class="text-teal-700 hover:underline">← Lessons</a>
        @else
            <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'course_id' => $lesson->course_id]) }}" class="text-teal-700 hover:underline">← Course-level lessons</a>
        @endif
    </div>

    <h2 class="text-lg font-semibold">Edit lesson</h2>

    <form method="POST" action="{{ route('coach.lessons.update', [$tenant, $lesson]) }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @if ($lesson->module_id !== null)
            <div>
                <label for="module_id" class="block text-sm font-medium text-stone-700">Module</label>
                <select id="module_id" name="module_id" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm">
                    @foreach ($modulesInCourse as $mod)
                        <option value="{{ $mod->id }}" @selected(old('module_id', $lesson->module_id) == $mod->id)>{{ $mod->title }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-stone-500">Switch between modules in this course only.</p>
            </div>
        @else
            <p class="rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-700">
                <strong>Course-level lesson</strong> — not inside a module. To organize under a module, create a module first, then add a new lesson there.
            </p>
        @endif
        @include('coach.partials.lesson-fields', ['lesson' => $lesson])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
    </form>
@endsection
