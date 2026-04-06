@extends('layouts.app')

@section('title', 'Edit course')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $course->program_id]) }}" class="text-teal-700 hover:underline">← Courses</a>
    </div>

    <h2 class="text-lg font-semibold">Edit course</h2>

    <form method="POST" action="{{ route('coach.courses.update', [$tenant, $course]) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="program_id" class="block text-sm font-medium text-stone-700">Program</label>
            <select id="program_id" name="program_id" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm">
                @foreach (\App\Models\Program::query()->where('tenant_id', $tenant->id)->orderBy('title')->get() as $prog)
                    <option value="{{ $prog->id }}" @selected(old('program_id', $course->program_id) == $prog->id)>{{ $prog->title }}</option>
                @endforeach
            </select>
        </div>
        @include('coach.partials.course-fields', ['course' => $course])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
    </form>
@endsection
