@extends('layouts.app')

@section('title', 'New module')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id]) }}" class="text-teal-700 hover:underline">← Modules</a>
    </div>

    <h2 class="text-lg font-semibold">New module in {{ $course->title }}</h2>

    <form method="POST" action="{{ route('coach.modules.store', $tenant) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        <input type="hidden" name="course_id" value="{{ $course->id }}">
        @include('coach.partials.module-fields', ['module' => null])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Create</button>
    </form>
@endsection
