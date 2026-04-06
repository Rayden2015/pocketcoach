@extends('layouts.app')

@section('title', 'Edit lesson')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $lesson->module_id]) }}" class="text-teal-700 hover:underline">← Lessons</a>
    </div>

    <h2 class="text-lg font-semibold">Edit lesson</h2>

    <form method="POST" action="{{ route('coach.lessons.update', [$tenant, $lesson]) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="module_id" class="block text-sm font-medium text-stone-700">Module</label>
            <select id="module_id" name="module_id" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm">
                @foreach (\App\Models\Module::query()->where('tenant_id', $tenant->id)->with('course')->orderBy('title')->get() as $mod)
                    <option value="{{ $mod->id }}" @selected(old('module_id', $lesson->module_id) == $mod->id)>{{ $mod->course->title }} — {{ $mod->title }}</option>
                @endforeach
            </select>
        </div>
        @include('coach.partials.lesson-fields', ['lesson' => $lesson])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
    </form>
@endsection
