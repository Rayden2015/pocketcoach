@extends('layouts.app')

@section('title', 'Invitation — '.$tenant->name)

@section('content')
    <div class="mx-auto max-w-lg">
        <p class="text-xs font-medium uppercase tracking-wide text-teal-700">{{ $tenant->name }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-stone-900">Invitation unavailable</h1>
        <p class="mt-4 rounded-2xl border border-stone-200 bg-white p-6 text-sm text-stone-700 shadow-sm">
            {{ $message }}
        </p>
        <p class="mt-4 text-sm">
            <a href="{{ route('public.catalog', $tenant) }}" class="text-teal-700 hover:underline">Back to catalog</a>
        </p>
    </div>
@endsection
