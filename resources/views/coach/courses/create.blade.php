@extends('layouts.app')

@section('title', 'New course')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $program->id]) }}" class="text-teal-700 hover:underline">← Courses</a>
    </div>

    <h2 class="text-lg font-semibold">New course in {{ $program->title }}</h2>

    <form method="POST" action="{{ route('coach.courses.store', $tenant) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        <input type="hidden" name="program_id" value="{{ $program->id }}">
        @include('coach.partials.course-fields', ['course' => null])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Create</button>
    </form>
@endsection
