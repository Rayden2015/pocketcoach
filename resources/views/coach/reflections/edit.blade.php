@extends('layouts.app')

@section('title', 'Edit reflection — '.$tenant->name)

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 text-sm">
        <a href="{{ route('coach.reflections.index', $tenant) }}" class="text-teal-700 hover:underline">← Reflections</a>
    </div>

    @if ($prompt->is_published)
        <form method="POST" action="{{ route('coach.reflections.update', [$tenant, $prompt]) }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <p class="rounded-lg bg-teal-50 px-3 py-2 text-xs text-teal-900">This prompt is live. To schedule a new daily post, create another reflection.</p>
            <div>
                <label for="title" class="block text-sm font-medium text-stone-700">Title (optional)</label>
                <input id="title" name="title" type="text" value="{{ old('title', $prompt->title) }}"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="body" class="block text-sm font-medium text-stone-700">Prompt / questions</label>
                <textarea id="body" name="body" rows="10" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('body', $prompt->body) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $prompt->is_published))>
                Published (uncheck to unpublish and hide from learners)
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Update</button>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('coach.reflections.update', [$tenant, $prompt]) }}" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <div>
                <label for="title" class="block text-sm font-medium text-stone-700">Title (optional)</label>
                <input id="title" name="title" type="text" value="{{ old('title', $prompt->title) }}"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="body" class="block text-sm font-medium text-stone-700">Prompt / questions</label>
                <textarea id="body" name="body" rows="10" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('body', $prompt->body) }}</textarea>
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-stone-800">When to publish</legend>
                <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                    <input type="radio" name="publish_timing" value="schedule" class="mt-1" @checked(old('publish_timing', 'schedule') === 'schedule')>
                    <span>
                        <span class="font-medium">Schedule</span>
                        <span class="block text-xs text-stone-500">Default time is 7:00 in {{ config('app.timezone') }}.</span>
                    </span>
                </label>
                <div class="ml-6 grid gap-3 border-l border-stone-200 pl-4 sm:grid-cols-2">
                    <div>
                        <label for="scheduled_date" class="block text-xs font-medium text-stone-600">Date</label>
                        <input id="scheduled_date" name="scheduled_date" type="date" value="{{ old('scheduled_date', $defaultScheduleDate) }}"
                            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="scheduled_time" class="block text-xs font-medium text-stone-600">Time</label>
                        <input id="scheduled_time" name="scheduled_time" type="time" value="{{ old('scheduled_time', $defaultScheduleTime) }}"
                            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                </div>

                <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                    <input type="radio" name="publish_timing" value="now" class="mt-1" @checked(old('publish_timing', 'schedule') === 'now')>
                    <span>
                        <span class="font-medium">Publish now</span>
                        <span class="block text-xs text-stone-500">Notify learners immediately per space settings.</span>
                    </span>
                </label>
            </fieldset>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Update</button>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ route('coach.reflections.destroy', [$tenant, $prompt]) }}" class="mt-6" onsubmit="return confirm('Delete this prompt and all responses?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-600 hover:underline">Delete prompt</button>
    </form>
@endsection
