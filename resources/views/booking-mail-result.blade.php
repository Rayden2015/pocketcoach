@extends('layouts.app')

@section('title', $tenant->name.' — booking response')

@section('content')
    <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-lg font-semibold text-stone-900">
            @if ($outcome === 'confirmed')
                Request confirmed
            @elseif ($outcome === 'declined')
                Request declined
            @else
                Response
            @endif
        </h1>
        <p class="mt-3 text-sm text-stone-600">
            @if (session('status'))
                {{ session('status') }}
            @elseif (session('warning'))
                {{ session('warning') }}
            @else
                No change was made.
            @endif
        </p>
        <div class="mt-6 flex flex-wrap gap-2 text-sm">
            <a href="{{ route('coach.bookings.index', $tenant) }}" class="inline-flex rounded-full bg-teal-600 px-4 py-2 font-semibold text-white shadow-sm hover:bg-teal-700">
                Coach bookings
            </a>
            <a href="{{ route('public.catalog', $tenant) }}" class="inline-flex rounded-full border border-stone-300 px-4 py-2 font-medium text-stone-800 hover:bg-stone-50">
                Space catalog
            </a>
        </div>
    </div>
@endsection
