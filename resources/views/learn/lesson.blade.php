@extends('layouts.app')

@section('title', $lesson->title.' — '.$course->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('learn.course', [$tenant, $course]) }}" class="font-medium text-teal-700 hover:underline">← Course overview</a>
            <span class="hidden text-stone-300 sm:inline">|</span>
            <a href="{{ route('my-learning') }}" class="text-stone-600 hover:text-teal-800">My learning</a>
        </div>
        <div class="flex gap-3">
            @if ($prevLesson)
                <a href="{{ route('learn.lesson', [$tenant, $prevLesson]) }}" class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-stone-700 shadow-sm hover:border-stone-400">Previous</a>
            @endif
            @if ($nextLesson)
                <a href="{{ route('learn.lesson', [$tenant, $nextLesson]) }}" class="rounded-full bg-stone-900 px-3 py-1.5 font-semibold text-white shadow-sm hover:bg-stone-800">Next lesson</a>
            @endif
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start lg:gap-8">
        <div class="min-w-0">
            <article class="overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-sm">
                <div class="border-b border-stone-100 bg-stone-50/80 px-5 py-3 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ str_replace('_', ' ', $lesson->lesson_type) }}</p>
                    <h1 class="mt-1 text-xl font-bold tracking-tight text-stone-900 sm:text-2xl">{{ $lesson->title }}</h1>
                    <p class="mt-1 text-xs text-stone-500">{{ $course->title }}</p>
                </div>
                <div class="p-5 sm:p-6">
                    @php($mediaUrl = $lesson->resolvedMediaUrl())
                    @if ($mediaUrl)
                        <div class="lesson-media">
                            @switch($lesson->lesson_type)
                                @case(\App\Models\Lesson::TYPE_VIDEO)
                                    @if ($embed = \App\Models\Lesson::youtubeEmbedUrl($mediaUrl))
                                        <div class="aspect-video w-full overflow-hidden rounded-xl bg-stone-900 shadow-inner">
                                            <iframe src="{{ $embed }}" class="h-full w-full" title="Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                        </div>
                                    @else
                                        <video src="{{ $mediaUrl }}" controls class="w-full max-h-[min(70vh,520px)] rounded-xl bg-black shadow-lg" preload="metadata" playsinline controlsList="nodownload"></video>
                                    @endif
                                    @break
                                @case(\App\Models\Lesson::TYPE_AUDIO)
                                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                                        <audio src="{{ $mediaUrl }}" controls class="w-full" preload="metadata"></audio>
                                    </div>
                                    @break
                                @case(\App\Models\Lesson::TYPE_IMAGE)
                                    <img src="{{ $mediaUrl }}" alt="" class="max-h-[min(70vh,560px)] w-full rounded-xl bg-stone-100 object-contain shadow-sm">
                                    @break
                                @case(\App\Models\Lesson::TYPE_PDF)
                                    <div class="min-h-[28rem] w-full overflow-hidden rounded-xl border border-stone-200 bg-stone-50 shadow-inner sm:min-h-[32rem]">
                                        <iframe src="{{ $mediaUrl }}" class="h-[28rem] w-full sm:h-[32rem]" title="PDF"></iframe>
                                    </div>
                                    @break
                                @default
                                    <div class="min-h-[20rem] w-full overflow-hidden rounded-xl border border-stone-200 bg-stone-50">
                                        <iframe src="{{ $mediaUrl }}" class="h-[20rem] w-full" title="Lesson material" allowfullscreen loading="lazy"></iframe>
                                    </div>
                            @endswitch
                        </div>
                    @endif

                    @if ($lesson->body)
                        <div class="prose prose-stone mt-6 max-w-none text-stone-800 prose-p:leading-relaxed">
                            {!! nl2br(e($lesson->body)) !!}
                        </div>
                    @endif
                </div>
            </article>

            <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-sm font-bold text-stone-900">Notes &amp; progress</h2>
                <form method="POST" action="{{ route('learn.lesson.progress', [$tenant, $lesson]) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="notes" class="block text-sm font-medium text-stone-700">Your notes</label>
                        <textarea id="notes" name="notes" rows="4"
                            class="mt-1 w-full rounded-xl border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/25">{{ old('notes', $progress->notes ?? '') }}</textarea>
                    </div>
                    <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                        <input type="checkbox" name="notes_is_public" value="1" class="mt-1 rounded border-stone-300 text-teal-600 focus:ring-teal-500"
                            @checked(old('notes_is_public', $progress->notes_is_public ?? false))>
                        <span><span class="font-medium">Share with other learners</span> — when checked, enrolled learners can read this note on this lesson page (not on other lessons).</span>
                    </label>
                    <input type="hidden" name="mark_complete" value="0">
                    <label class="flex items-center gap-2 text-sm text-stone-700">
                        <input type="checkbox" name="mark_complete" value="1" @checked(old('mark_complete', $progress && $progress->completed_at ? '1' : '0') === '1')>
                        Mark lesson complete
                    </label>
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">Save</button>
                </form>
            </section>

            @if ($progress)
                <section class="mt-6 rounded-2xl border border-amber-200/80 bg-amber-50/40 p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-bold text-stone-900">Coach conversation</h2>
                    <p class="mt-1 text-xs text-stone-600">Message your coach about this lesson. Only you and coaches in this space can see this thread.</p>
                    <a href="{{ route('submission-conversations.lesson.show', [$tenant, $progress]) }}"
                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-900">
                        Open conversation
                        @if (($progress->conversation_messages_count ?? 0) > 0)
                            <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-medium">{{ $progress->conversation_messages_count }} messages</span>
                        @endif
                    </a>
                </section>
            @endif

            @if ($publicPeerNotes->isNotEmpty())
                <section class="mt-6 rounded-2xl border border-teal-100 bg-teal-50/40 p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-bold text-stone-900">From other learners</h2>
                    <p class="mt-1 text-xs text-stone-600">Notes they chose to share on this lesson.</p>
                    <ul class="mt-4 space-y-4">
                        @foreach ($publicPeerNotes as $peer)
                            <li class="rounded-xl border border-teal-100/80 bg-white p-4 text-sm shadow-sm">
                                <p class="font-medium text-stone-900">{{ $peer->user?->name ?? 'Learner' }}</p>
                                <p class="mt-2 whitespace-pre-wrap text-stone-800">{{ $peer->notes }}</p>
                                <p class="mt-2 text-xs text-stone-500">Updated {{ $peer->updated_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="mt-8 lg:hidden">
                @include('learn.partials.curriculum-tree', [
                    'tenant' => $tenant,
                    'course' => $course,
                    'canAccess' => true,
                    'currentLesson' => $lesson,
                    'completedLessonIds' => $completedLessonIds,
                ])
            </div>
        </div>

        <aside class="sticky top-20 mt-8 hidden lg:mt-0 lg:block">
            @include('learn.partials.curriculum-tree', [
                'tenant' => $tenant,
                'course' => $course,
                'canAccess' => true,
                'currentLesson' => $lesson,
                'completedLessonIds' => $completedLessonIds,
            ])
        </aside>
    </div>
@endsection
