@extends('layouts.studio')

@section('title', $lesson->title.' — studio')

@php
    $initialProgressPercent = 0;
    if ($progress?->completed_at) {
        $initialProgressPercent = 100;
    } elseif ($progress?->content_progress_percent !== null) {
        $initialProgressPercent = min(100, (int) $progress->content_progress_percent);
    }
    $isImage = $lesson->lesson_type === \App\Models\Lesson::TYPE_IMAGE;
@endphp

@section('content')
    <div
        id="lesson-studio"
        class="flex h-[100dvh] flex-col"
        data-learn-lesson
        data-learn-studio
        data-progress-url="{{ route('learn.lesson.progress', [$tenant, $lesson]) }}"
        data-initial-percent="{{ $initialProgressPercent }}"
        data-completed="{{ $progress && $progress->completed_at ? '1' : '0' }}"
        data-lesson-type="{{ $lesson->lesson_type }}"
    >
        <header class="flex shrink-0 flex-wrap items-center gap-2 border-b border-white/10 bg-stone-950/95 px-3 py-2 sm:px-4">
            <a href="{{ route('learn.lesson', [$tenant, $lesson]) }}" class="rounded-full border border-white/15 px-3 py-1.5 text-xs font-semibold text-stone-200 hover:bg-white/10">
                ← Exit studio
            </a>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-semibold uppercase tracking-wide text-teal-300/90">{{ str_replace('_', ' ', $lesson->lesson_type) }} studio</p>
                <h1 class="truncate text-sm font-semibold text-white sm:text-base">{{ $lesson->title }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-studio-toggle-notes class="rounded-full border border-white/15 px-3 py-1.5 text-xs font-semibold text-stone-200 hover:bg-white/10 md:hidden">
                    Notes
                </button>
                <button type="button" data-studio-fullscreen class="rounded-full bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-500">
                    Fullscreen
                </button>
                @if ($isImage)
                    <div class="flex items-center gap-1" role="group" aria-label="Zoom">
                        <button type="button" data-studio-zoom-out class="rounded-full border border-white/15 px-2.5 py-1.5 text-xs font-semibold text-stone-200 hover:bg-white/10">−</button>
                        <button type="button" data-studio-zoom-reset class="rounded-full border border-white/15 px-2.5 py-1.5 text-xs font-semibold text-stone-200 hover:bg-white/10">100%</button>
                        <button type="button" data-studio-zoom-in class="rounded-full border border-white/15 px-2.5 py-1.5 text-xs font-semibold text-stone-200 hover:bg-white/10">+</button>
                    </div>
                @endif
                @if ($nextLesson)
                    <button type="submit" form="lesson-progress-form" name="intent" value="next" class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-stone-900 hover:bg-stone-200">
                        Next
                    </button>
                @endif
            </div>
        </header>

        <div class="relative flex min-h-0 flex-1 flex-col md:flex-row">
            <div class="relative min-h-0 min-w-0 flex-1 overflow-hidden bg-black" data-studio-stage>
                @include('learn.partials.lesson-media', ['lesson' => $lesson, 'studioMode' => true])
                @unless ($lesson->resolvedMediaUrl())
                    <div class="flex h-full items-center justify-center p-8 text-sm text-stone-400">No media uploaded for this lesson.</div>
                @endunless
            </div>

            <aside
                data-studio-notes
                class="flex max-h-[42vh] w-full shrink-0 flex-col border-t border-white/10 bg-stone-900 transition-[max-height] md:max-h-none md:w-[22rem] md:border-l md:border-t-0 lg:w-[26rem]"
            >
                <div class="border-b border-white/10 px-4 py-3">
                    <h2 class="text-sm font-bold text-white">Your notes</h2>
                    <p class="mt-0.5 text-xs text-stone-400">Keep notes visible while you study the media.</p>
                </div>
                <form method="POST" action="{{ route('learn.lesson.progress', [$tenant, $lesson]) }}" id="lesson-progress-form" class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto p-4">
                    @csrf
                    <input type="hidden" name="return_to" value="studio">
                    <input type="hidden" name="content_progress_percent" id="lesson-form-content-progress" value="{{ $initialProgressPercent }}">
                    <input type="hidden" name="position_seconds" id="lesson-form-position-seconds" value="{{ (int) ($progress->position_seconds ?? 0) }}">
                    @if ($lesson->body)
                        <div class="rounded-xl border border-white/10 bg-stone-950/60 p-3 text-xs leading-relaxed text-stone-300">
                            {!! nl2br(e($lesson->body)) !!}
                        </div>
                    @endif
                    <textarea id="notes" name="notes" rows="10"
                        class="min-h-[10rem] flex-1 rounded-xl border border-white/10 bg-stone-950 px-3 py-2 text-sm text-stone-100 shadow-inner focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                        placeholder="Write while you watch or inspect the media…">{{ old('notes', $progress->notes ?? '') }}</textarea>
                    <label class="flex cursor-pointer items-start gap-2 text-xs text-stone-300">
                        <input type="checkbox" name="notes_is_public" value="1" class="mt-0.5 rounded border-stone-600 bg-stone-950 text-teal-600 focus:ring-teal-500"
                            @checked(old('notes_is_public', $progress->notes_is_public ?? false))>
                        <span>Share this note with other learners on the lesson page</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" name="intent" value="save_notes" class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold text-white hover:bg-white/10">
                            Save notes
                        </button>
                        @if ($progress && $progress->completed_at)
                            <button type="submit" name="intent" value="incomplete" class="rounded-full border border-amber-400/40 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-100">
                                Mark incomplete
                            </button>
                        @else
                            <button type="submit" name="intent" value="complete" class="rounded-full bg-teal-600 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-500">
                                Mark complete
                            </button>
                        @endif
                    </div>
                    @if (session('status'))
                        <p class="text-xs text-teal-300">{{ session('status') }}</p>
                    @endif
                </form>
            </aside>
        </div>

        {{-- Hidden progress bar hook for learn-lesson.js --}}
        <div id="lesson-reading-progress" class="hidden" role="progressbar" aria-valuenow="{{ $initialProgressPercent }}">
            <div id="lesson-progress-fill" style="width: {{ $initialProgressPercent }}%"></div>
        </div>
    </div>
@endsection
