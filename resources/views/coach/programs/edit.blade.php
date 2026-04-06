@extends('layouts.app')

@section('title', 'Edit program')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.programs.index', $tenant) }}" class="text-teal-700 hover:underline">← Programs</a>
    </div>

    <h2 class="text-lg font-semibold">Edit program</h2>

    <form method="POST" action="{{ route('coach.programs.update', [$tenant, $program]) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('coach.partials.program-fields', ['program' => $program])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
    </form>
@endsection
