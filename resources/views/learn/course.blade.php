@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <div class="mb-4 text-sm">
        <a href="{{ route('learn.catalog', $tenant) }}" class="font-medium text-teal-700 hover:underline">← Back to catalog</a>
        <span class="text-stone-400">·</span>
        <a href="{{ route('my-learning') }}" class="text-stone-600 hover:text-teal-800">My learning</a>
    </div>

    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start lg:gap-8">
        <div class="min-w-0">
            <div class="relative overflow-hidden rounded-2xl shadow-lg">
                @if ($course->resolvedImageUrl())
                    <img src="{{ $course->resolvedImageUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-stone-950/95 via-stone-900/75 to-stone-900/50"></div>
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-stone-900 to-teal-900"></div>
                @endif
                <div class="relative px-6 py-8 text-white sm:px-8">
                @if ($course->program)
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-200/90">{{ $course->program->title }}</p>
                @endif
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ $course->title }}</h1>
                @if ($course->summary)
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-stone-300">{{ $course->summary }}</p>
                @endif
                @if (($reviewsSummary['reviews_count'] ?? 0) > 0)
                    <div class="mt-4">
                        @include('learn.partials.course-rating-stars', [
                            'rating' => $reviewsSummary['average_rating'],
                            'count' => $reviewsSummary['reviews_count'],
                            'size' => 'lg',
                            'class' => 'text-white [&_span:last-child]:text-stone-200',
                        ])
                    </div>
                @endif
                @if ($canAccess && $lessonsTotal > 0)
                    <div class="mt-6 max-w-md">
                        <div class="flex items-center justify-between text-xs font-medium text-stone-300">
                            <span>Your progress</span>
                            <span>{{ $lessonsCompleted }} / {{ $lessonsTotal }} lessons ({{ $courseProgressPercent }}%)</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/20">
                            <div class="h-full rounded-full bg-teal-400 transition-all" style="width: {{ $courseProgressPercent }}%"></div>
                        </div>
                    </div>
                @endif
                </div>
            </div>

            @if (! $canAccess)
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950">
                    <p class="font-semibold">You are not enrolled in this course yet.</p>
                    @if ($freeProductId)
                        <p class="mt-2 text-amber-900/90">This course is free. Enroll here to unlock lessons and track your progress.</p>
                        <form method="POST" action="{{ route('learn.course.enroll', [$tenant, $course]) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="rounded-full bg-stone-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-stone-800">
                                Enroll free
                            </button>
                        </form>
                    @else
                        <p class="mt-2">Self-enrollment is not enabled for this course yet. Ask your coach to turn on <strong>Allow free self-enrollment</strong> when editing the course.</p>
                    @endif
                </div>
            @endif

            @if (session('status'))
                <p class="mt-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
            @endif

            @error('enroll')
                <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $message }}</p>
            @enderror

            @if ($canAccess)
                @include('learn.partials.course-review-form', [
                    'tenant' => $tenant,
                    'course' => $course,
                    'myReview' => $myReview,
                    'reviewsSummary' => $reviewsSummary,
                ])
            @endif

            <div class="mt-8 lg:hidden">
                @include('learn.partials.curriculum-tree', [
                    'tenant' => $tenant,
                    'course' => $course,
                    'canAccess' => $canAccess,
                    'currentLesson' => null,
                    'completedLessonIds' => $completedLessonIds,
                ])
            </div>
        </div>

        <aside class="sticky top-20 mt-8 hidden lg:mt-0 lg:block">
            @include('learn.partials.curriculum-tree', [
                'tenant' => $tenant,
                'course' => $course,
                'canAccess' => $canAccess,
                'currentLesson' => null,
                'completedLessonIds' => $completedLessonIds,
            ])
        </aside>
    </div>
@endsection
