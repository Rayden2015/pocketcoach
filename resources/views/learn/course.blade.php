@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <div class="mb-6 text-sm">
        <a href="{{ route('learn.catalog', $tenant) }}" class="text-teal-700 hover:underline">← Catalog</a>
    </div>
    <h1 class="text-2xl font-semibold tracking-tight">{{ $course->title }}</h1>
    @if ($course->summary)
        <p class="mt-2 text-stone-600">{{ $course->summary }}</p>
    @endif

    @foreach ($course->modules as $module)
        <section class="mt-8">
            <h2 class="border-b border-stone-200 pb-2 text-sm font-semibold uppercase tracking-wide text-stone-500">{{ $module->title }}</h2>
            <ol class="mt-3 space-y-2">
                @foreach ($module->lessons as $lesson)
                    <li>
                        <a href="{{ route('learn.lesson', [$tenant, $lesson]) }}" class="block rounded-lg border border-transparent px-2 py-2 text-stone-800 hover:bg-white hover:shadow-sm">
                            {{ $lesson->title }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endforeach
@endsection
