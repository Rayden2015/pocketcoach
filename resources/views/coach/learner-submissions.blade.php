@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $subUrl = route('coach.learner-submissions.index', $tenant);
@endphp

@section('title', $tenant->name.' — learner submissions')

@section('content')
    @include('coach.partials.header')

    @if (session('status'))
        <p class="mb-4 rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ session('status') }}</p>
    @endif

    <div class="mb-6 flex flex-wrap gap-2 border-b border-stone-200 pb-3 text-sm">
        <a href="{{ $subUrl }}?tab=reflections"
            class="rounded-full px-4 py-2 font-medium {{ $tab === 'reflections' ? 'bg-amber-100 text-amber-950' : 'text-stone-600 hover:bg-stone-100' }}">Daily reflections</a>
        <a href="{{ $subUrl }}?tab=lessons"
            class="rounded-full px-4 py-2 font-medium {{ $tab === 'lessons' ? 'bg-teal-100 text-teal-950' : 'text-stone-600 hover:bg-stone-100' }}">Lesson notes</a>
    </div>

    @if ($tab === 'reflections')
        <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-lg font-semibold text-stone-900">Daily reflection responses</h2>
            <p class="mt-1 text-sm text-stone-600">
                Submissions from learners on your prompts. <strong>Public</strong> entries are also visible to other learners on the reflection page.
            </p>
            <form method="GET" action="{{ $subUrl }}" class="mt-4 flex flex-wrap items-end gap-3">
                <input type="hidden" name="tab" value="reflections">
                <div class="min-w-[12rem] flex-1">
                    <label for="filter-prompt" class="block text-xs font-medium uppercase tracking-wide text-stone-500">Filter by prompt</label>
                    <select id="filter-prompt" name="prompt" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" onchange="this.form.submit()">
                        <option value="">All prompts</option>
                        @foreach ($promptsForFilter as $p)
                            <option value="{{ $p->id }}" @selected($selectedPromptId === $p->id)>
                                {{ $p->title ?: 'Untitled' }}
                                @if ($p->published_at)
                                    — {{ $p->published_at->timezone(config('app.timezone'))->format('M j, Y') }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('coach.reflections.index', $tenant) }}" class="text-sm font-medium text-teal-800 hover:underline">Manage prompts</a>
            </form>
        </div>

        <div class="hidden overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm lg:block">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Last updated</th>
                        <th class="px-4 py-3">Learner</th>
                        <th class="px-4 py-3">Prompt</th>
                        <th class="px-4 py-3">Public</th>
                        <th class="px-4 py-3">Conversation</th>
                        <th class="px-4 py-3">Reflection</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($responses as $row)
                        @php
                            $prompt = $row->reflectionPrompt;
                            $plainPrompt = $prompt ? Str::limit(preg_replace('/\s+/', ' ', trim(strip_tags($prompt->body ?? ''))), 160) : '';
                        @endphp
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                <time datetime="{{ $row->updated_at?->toIso8601String() }}">{{ $row->updated_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</time>
                                @if ($row->first_submitted_at && $row->first_submitted_at->ne($row->updated_at))
                                    <p class="mt-1 text-xs text-stone-500">First: {{ $row->first_submitted_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-stone-900">{{ $row->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-stone-500">{{ $row->user?->email }}</p>
                            </td>
                            <td class="max-w-xs px-4 py-3">
                                @if ($prompt)
                                    <p class="font-medium text-stone-900">{{ $prompt->title ?: 'Untitled' }}</p>
                                    <p class="mt-1 text-xs text-stone-600">{{ $plainPrompt }}</p>
                                    <a href="{{ route('coach.reflections.edit', [$tenant, $prompt]) }}" class="mt-2 inline-block text-xs text-teal-700 hover:underline">Edit prompt</a>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($row->is_public)
                                    <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-800">Yes</span>
                                @else
                                    <span class="text-stone-400">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('submission-conversations.reflection.show', [$tenant, $row]) }}" class="font-medium text-teal-800 hover:underline">Open</a>
                                @if (($row->conversation_messages_count ?? 0) > 0)
                                    <span class="ml-1 text-xs text-stone-500">({{ $row->conversation_messages_count }})</span>
                                @endif
                            </td>
                            <td class="max-w-md px-4 py-3 text-stone-800">
                                <div class="prose prose-sm prose-stone max-w-none">
                                    {!! Str::markdown($row->body ?? '') !!}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-stone-500">No reflection responses yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="space-y-4 lg:hidden">
            @forelse ($responses as $row)
                @php
                    $prompt = $row->reflectionPrompt;
                    $plainPrompt = $prompt ? Str::limit(preg_replace('/\s+/', ' ', trim(strip_tags($prompt->body ?? ''))), 200) : '';
                @endphp
                <li class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Updated</p>
                    <p class="text-sm text-stone-800">{{ $row->updated_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Learner</p>
                    <p class="font-medium text-stone-900">{{ $row->user?->name ?? 'Unknown' }}</p>
                    <p class="text-xs text-stone-500">{{ $row->user?->email }}</p>
                    <p class="mt-2 text-xs">Public: <strong>{{ $row->is_public ? 'Yes' : 'No' }}</strong></p>
                    <p class="mt-2 text-xs">
                        <a href="{{ route('submission-conversations.reflection.show', [$tenant, $row]) }}" class="font-medium text-teal-800 hover:underline">Conversation</a>
                        @if (($row->conversation_messages_count ?? 0) > 0)
                            <span class="text-stone-500">({{ $row->conversation_messages_count }} messages)</span>
                        @endif
                    </p>
                    @if ($prompt)
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Prompt</p>
                        <p class="font-medium text-stone-900">{{ $prompt->title ?: 'Untitled' }}</p>
                        <p class="mt-1 text-sm text-stone-600">{{ $plainPrompt }}</p>
                    @endif
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Reflection</p>
                    <div class="prose prose-sm prose-stone mt-1 max-w-none text-stone-800">
                        {!! Str::markdown($row->body ?? '') !!}
                    </div>
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-10 text-center text-sm text-stone-500">No reflection responses yet.</li>
            @endforelse
        </ul>

        <div class="mt-6">
            {{ $responses->links() }}
        </div>
    @else
        <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-lg font-semibold text-stone-900">Lesson notes</h2>
            <p class="mt-1 text-sm text-stone-600">
                Notes learners save on lessons. <strong>Public</strong> notes appear to peers on the lesson page. Private notes are still visible to you here.
            </p>
        </div>

        <div class="hidden overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm lg:block">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3">Learner</th>
                        <th class="px-4 py-3">Course / lesson</th>
                        <th class="px-4 py-3">Public</th>
                        <th class="px-4 py-3">Conversation</th>
                        <th class="px-4 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($lessonProgress as $row)
                        @php
                            $les = $row->lesson;
                            $courseTitle = $les?->courseForDisplay()?->title ?? '—';
                            $lessonTitle = $les?->title ?? '—';
                        @endphp
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 text-stone-700">
                                <time datetime="{{ $row->updated_at?->toIso8601String() }}">{{ $row->updated_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</time>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-stone-900">{{ $row->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-stone-500">{{ $row->user?->email }}</p>
                            </td>
                            <td class="max-w-xs px-4 py-3">
                                <p class="text-xs text-stone-500">{{ $courseTitle }}</p>
                                <p class="font-medium text-stone-900">{{ $lessonTitle }}</p>
                                @if ($les)
                                    <a href="{{ route('learn.lesson', [$tenant, $les]) }}" class="mt-1 inline-block text-xs text-teal-700 hover:underline">Open lesson</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($row->notes_is_public)
                                    <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-800">Yes</span>
                                @else
                                    <span class="text-stone-400">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('submission-conversations.lesson.show', [$tenant, $row]) }}" class="font-medium text-teal-800 hover:underline">Open</a>
                                @if (($row->conversation_messages_count ?? 0) > 0)
                                    <span class="ml-1 text-xs text-stone-500">({{ $row->conversation_messages_count }})</span>
                                @endif
                            </td>
                            <td class="max-w-md px-4 py-3 text-stone-800">
                                <p class="whitespace-pre-wrap text-sm">{{ $row->notes }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-stone-500">No lesson notes yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="space-y-4 lg:hidden">
            @forelse ($lessonProgress as $row)
                @php
                    $les = $row->lesson;
                    $courseTitle = $les?->courseForDisplay()?->title ?? '—';
                @endphp
                <li class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Updated</p>
                    <p class="text-sm text-stone-800">{{ $row->updated_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Learner</p>
                    <p class="font-medium text-stone-900">{{ $row->user?->name ?? 'Unknown' }}</p>
                    <p class="text-xs text-stone-500">{{ $row->user?->email }}</p>
                    <p class="mt-2 text-xs">Public: <strong>{{ $row->notes_is_public ? 'Yes' : 'No' }}</strong></p>
                    <p class="mt-2 text-xs">
                        <a href="{{ route('submission-conversations.lesson.show', [$tenant, $row]) }}" class="font-medium text-teal-800 hover:underline">Conversation</a>
                        @if (($row->conversation_messages_count ?? 0) > 0)
                            <span class="text-stone-500">({{ $row->conversation_messages_count }} messages)</span>
                        @endif
                    </p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Course / lesson</p>
                    <p class="text-xs text-stone-600">{{ $courseTitle }}</p>
                    <p class="font-medium text-stone-900">{{ $les?->title ?? '—' }}</p>
                    @if ($les)
                        <a href="{{ route('learn.lesson', [$tenant, $les]) }}" class="mt-1 inline-block text-xs text-teal-700 hover:underline">Open lesson</a>
                    @endif
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Notes</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-stone-800">{{ $row->notes }}</p>
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-10 text-center text-sm text-stone-500">No lesson notes yet.</li>
            @endforelse
        </ul>

        <div class="mt-6">
            {{ $lessonProgress->links() }}
        </div>
    @endif
@endsection
