@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', 'Conversation — '.$tenant->name)

@section('content')
    @if ($isStaff)
        @include('coach.partials.header')
    @else
        <div class="mb-4 text-sm">
            <a href="{{ route('learn.reflections.show', [$tenant, $prompt]) }}" class="text-teal-700 hover:underline">← Back to reflection</a>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-stone-900">Conversation</h1>
        <p class="mt-1 text-sm text-stone-600">
            Thread for <strong>{{ $learner?->name ?? 'Learner' }}</strong>’s reflection
            @if ($prompt?->title)
                — <span class="text-stone-800">{{ $prompt->title }}</span>
            @endif
        </p>
        @if ($isStaff)
            <a href="{{ route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']) }}" class="mt-2 inline-block text-sm font-medium text-teal-800 hover:underline">← Learner submissions</a>
        @endif
    </div>

    <section class="mb-8 rounded-2xl border border-stone-200 bg-stone-50/80 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Original submission</p>
        <div class="prose prose-sm prose-stone mt-2 max-w-none text-stone-900">
            {!! Str::markdown($subject->body ?? '') !!}
        </div>
    </section>

    @include('submission-conversation.partials.message-thread', [
        'tenant' => $tenant,
        'messages' => $messages,
        'messageById' => $messageById,
        'messageDepth' => $messageDepth,
        'formAction' => route('submission-conversations.reflection.message', [$tenant, $subject]),
    ])
@endsection
