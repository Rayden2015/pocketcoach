@extends('layouts.app')

@section('title', 'Edit module')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $module->course_id]) }}" class="text-teal-700 hover:underline">← Modules</a>
    </div>

    <h2 class="text-lg font-semibold">Edit module</h2>

    <form method="POST" action="{{ route('coach.modules.update', [$tenant, $module]) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="course_id" class="block text-sm font-medium text-stone-700">Course</label>
            <select id="course_id" name="course_id" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm">
                @foreach (\App\Models\Course::query()->where('tenant_id', $tenant->id)->with('program')->orderBy('title')->get() as $c)
                    <option value="{{ $c->id }}" @selected(old('course_id', $module->course_id) == $c->id)>{{ $c->program->title }} — {{ $c->title }}</option>
                @endforeach
            </select>
        </div>
        @include('coach.partials.module-fields', ['module' => $module])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
    </form>
@endsection
