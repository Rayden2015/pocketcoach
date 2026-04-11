@extends('layouts.app')

@section('title', 'Conversation — '.$tenant->name)

@section('content')
    @php
        $course = $lesson?->courseForDisplay();
    @endphp

    @if ($isStaff)
        @include('coach.partials.header')
    @else
        <div class="mb-4 flex flex-wrap gap-2 text-sm">
            @if ($lesson)
                <a href="{{ route('learn.lesson', [$tenant, $lesson]) }}" class="text-teal-700 hover:underline">← Back to lesson</a>
            @endif
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-stone-900">Conversation</h1>
        <p class="mt-1 text-sm text-stone-600">
            Thread for <strong>{{ $learner?->name ?? 'Learner' }}</strong>’s notes
            @if ($lesson)
                — <span class="text-stone-800">{{ $lesson->title }}</span>
                @if ($course)
                    <span class="text-stone-500">({{ $course->title }})</span>
                @endif
            @endif
        </p>
        @if ($isStaff)
            <a href="{{ route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'lessons']) }}" class="mt-2 inline-block text-sm font-medium text-teal-800 hover:underline">← Learner submissions</a>
        @endif
    </div>

    <section class="mb-8 rounded-2xl border border-stone-200 bg-stone-50/80 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Original notes</p>
        <p class="mt-2 whitespace-pre-wrap text-sm text-stone-900">{{ $subject->notes }}</p>
    </section>

    @include('submission-conversation.partials.message-thread', [
        'tenant' => $tenant,
        'messages' => $messages,
        'messageById' => $messageById,
        'messageDepth' => $messageDepth,
        'formAction' => route('submission-conversations.lesson.message', [$tenant, $subject]),
    ])
@endsection
