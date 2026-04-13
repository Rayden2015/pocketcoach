@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', $tenant->name.' — catalog')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">{{ $tenant->name }}</h1>

    @if (! empty($catalogIntroMarkdown))
        <div class="prose prose-stone prose-sm mt-4 max-w-none">
            {!! Str::markdown($catalogIntroMarkdown) !!}
        </div>
    @endif

    <p class="mt-4 max-w-2xl text-xs leading-relaxed text-stone-500">
        @auth
            <a href="{{ route('learn.catalog', $tenant) }}" class="font-medium text-teal-800 underline decoration-teal-800/30 hover:decoration-teal-800" title="Your enrollments and lesson progress for this space.">Member catalog</a>
        @else
            <a href="{{ route('space.login', $tenant) }}" class="font-medium text-teal-800 underline decoration-teal-800/30 hover:decoration-teal-800">Log in</a>
            <span class="text-stone-400">·</span>
            <a href="{{ route('space.register', $tenant) }}" class="font-medium text-teal-800 underline decoration-teal-800/30 hover:decoration-teal-800" title="Required to enroll in courses on this space.">Register</a>
        @endauth
    </p>

    @if ($reflectionsEnabled && $latestReflection)
        <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-4">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-900">Today&rsquo;s reflection</p>
            @if ($latestReflection->title)
                <p class="mt-1 font-semibold text-stone-900">{{ $latestReflection->title }}</p>
            @endif
            <p class="mt-2 text-sm text-stone-700">{{ Str::limit(strip_tags($latestReflection->body), 240) }}</p>
            @auth
                <a href="{{ route('learn.reflections.show', [$tenant, $latestReflection]) }}"
                    class="mt-3 inline-flex rounded-full bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">Open reflection</a>
            @else
                <p class="mt-2 text-xs text-stone-600">Log in to respond to the coach&rsquo;s prompt.</p>
            @endauth
        </div>
    @endif

    <p class="mt-8 text-sm font-medium text-stone-800">Programs &amp; courses</p>
    <p class="mt-1 text-xs text-stone-500" title="Featured items appear first. Order can reflect popularity when the space tracks catalog views.">Featured listings sort first.</p>

    @foreach ($programs as $program)
        <section class="mt-8">
            <h2 class="text-lg font-semibold text-stone-900">
                {{ $program->title }}
                @if ($program->is_featured)
                    <span class="ml-2 rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-800">Featured</span>
                @endif
            </h2>
            @if ($program->summary)
                <p class="mt-1 text-sm text-stone-600">{{ $program->summary }}</p>
            @endif
            <ul class="mt-4 space-y-2">
                @foreach ($program->courses as $course)
                    <li class="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <span class="font-medium text-stone-900">{{ $course->title }}</span>
                                @if ($course->is_featured)
                                    <span class="ml-2 text-xs font-medium text-teal-700">Featured</span>
                                @endif
                                @if ($trackCatalogViews && $course->catalog_view_count > 0)
                                    <span class="ml-2 text-xs text-stone-400">{{ $course->catalog_view_count }} opens</span>
                                @endif
                                @if ($course->summary)
                                    <p class="mt-0.5 text-sm text-stone-600">{{ $course->summary }}</p>
                                @endif
                            </div>
                            @auth
                                <a href="{{ route('public.catalog.track', $tenant) }}?{{ http_build_query(['course_id' => $course->id, 'redirect_to' => \Illuminate\Support\Facades\URL::route('learn.course', [$tenant, $course], false)]) }}"
                                    class="shrink-0 text-sm font-medium text-teal-700 hover:underline">Open course</a>
                            @else
                                <a href="{{ route('space.login', $tenant) }}" class="shrink-0 text-sm font-medium text-teal-700 hover:underline">Log in to enroll</a>
                            @endauth
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @if ($standaloneCourses->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-semibold text-stone-900">Single courses</h2>
            <p class="mt-1 text-sm text-stone-600">Not grouped in a program.</p>
            <ul class="mt-4 space-y-2">
                @foreach ($standaloneCourses as $course)
                    <li class="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <span class="font-medium text-stone-900">{{ $course->title }}</span>
                                @if ($course->is_featured)
                                    <span class="ml-2 text-xs font-medium text-teal-700">Featured</span>
                                @endif
                                @if ($trackCatalogViews && $course->catalog_view_count > 0)
                                    <span class="ml-2 text-xs text-stone-400">{{ $course->catalog_view_count }} opens</span>
                                @endif
                                @if ($course->summary)
                                    <p class="mt-0.5 text-sm text-stone-600">{{ $course->summary }}</p>
                                @endif
                            </div>
                            @auth
                                <a href="{{ route('public.catalog.track', $tenant) }}?{{ http_build_query(['course_id' => $course->id, 'redirect_to' => \Illuminate\Support\Facades\URL::route('learn.course', [$tenant, $course], false)]) }}"
                                    class="shrink-0 text-sm font-medium text-teal-700 hover:underline">Open course</a>
                            @else
                                <a href="{{ route('space.login', $tenant) }}" class="shrink-0 text-sm font-medium text-teal-700 hover:underline">Log in to enroll</a>
                            @endauth
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($programs->isEmpty() && $standaloneCourses->isEmpty())
        <p class="mt-8 text-stone-600">Nothing published yet.</p>
    @endif
@endsection
