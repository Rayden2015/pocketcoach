@extends('layouts.app')

@section('title', 'New program')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.programs.index', $tenant) }}" class="text-teal-700 hover:underline">← Programs</a>
    </div>

    <h2 class="text-lg font-semibold">New program</h2>

    <form method="POST" action="{{ route('coach.programs.store', $tenant) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @include('coach.partials.program-fields', ['program' => null])
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Create</button>
    </form>
@endsection
