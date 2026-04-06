@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm">
        <a href="{{ route('learn.course', [$tenant, $course]) }}" class="text-teal-700 hover:underline">← {{ $course->title }}</a>
        <div class="flex gap-2">
            @if ($prevLesson)
                <a href="{{ route('learn.lesson', [$tenant, $prevLesson]) }}" class="text-stone-600 hover:text-teal-800">Previous</a>
            @endif
            @if ($nextLesson)
                <a href="{{ route('learn.lesson', [$tenant, $nextLesson]) }}" class="font-medium text-teal-700 hover:underline">Next</a>
            @endif
        </div>
    </div>

    <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-stone-500">{{ $lesson->lesson_type }}</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $lesson->title }}</h1>

        @if ($lesson->media_url)
            <div class="mt-6 aspect-video w-full overflow-hidden rounded-xl bg-stone-100">
                <iframe src="{{ $lesson->media_url }}" class="h-full w-full" title="Lesson media" allowfullscreen loading="lazy"></iframe>
            </div>
        @endif

        @if ($lesson->body)
            <div class="prose prose-stone mt-6 max-w-none text-stone-800">
                {!! nl2br(e($lesson->body)) !!}
            </div>
        @endif
    </article>

    <section class="mt-8 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-stone-900">Your notes &amp; progress</h2>
        <form method="POST" action="{{ route('learn.lesson.progress', [$tenant, $lesson]) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="notes" class="block text-sm font-medium text-stone-700">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('notes', $progress->notes ?? '') }}</textarea>
            </div>
            <input type="hidden" name="mark_complete" value="0">
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="mark_complete" value="1" @checked(old('mark_complete', $progress && $progress->completed_at ? '1' : '0') === '1')>
                Mark lesson complete
            </label>
            <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
        </form>
    </section>
@endsection
