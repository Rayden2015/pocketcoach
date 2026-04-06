@extends('layouts.app')

@section('title', $tenant->name.' — programs')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-stone-600">Programs structure your offers.</p>
        <a href="{{ route('coach.programs.create', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New program</a>
    </div>

    <ul class="mt-2 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($programs as $program)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <a href="{{ route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $program->id]) }}" class="font-medium text-stone-900 hover:text-teal-800">{{ $program->title }}</a>
                    <span class="ml-2 text-xs text-stone-500">{{ $program->slug }}</span>
                    <span class="ml-2 text-xs {{ $program->is_published ? 'text-teal-700' : 'text-stone-400' }}">
                        {{ $program->is_published ? 'Published' : 'Draft' }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('coach.programs.edit', [$tenant, $program]) }}" class="text-teal-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('coach.programs.destroy', [$tenant, $program]) }}" class="inline" onsubmit="return confirm('Delete this program and all nested content?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-stone-500">No programs yet. Create one to get started.</li>
        @endforelse
    </ul>
@endsection
