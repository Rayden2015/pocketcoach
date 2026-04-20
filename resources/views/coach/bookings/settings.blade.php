@extends('layouts.app')

@section('title', $tenant->name.' — booking setup')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4">
        <a href="{{ route('coach.bookings.index', $tenant) }}" class="text-sm font-medium text-teal-800 hover:underline">&larr; Back to bookings</a>
    </div>

    <form method="post" action="{{ route('coach.booking.settings.update', $tenant) }}" class="space-y-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Public booking</p>
            <h2 class="text-lg font-semibold text-stone-900">Settings</h2>
        </div>

        @php
            $enabledOld = old('enabled', $settings->enabled ? '1' : '0');
        @endphp
        <div>
            <label for="enabled" class="block text-sm font-medium text-stone-800">Accept public bookings</label>
            <select id="enabled" name="enabled" class="mt-1 w-full max-w-xs rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                <option value="1" @selected($enabledOld === '1')>Yes</option>
                <option value="0" @selected($enabledOld === '0')>No</option>
            </select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="slot_duration_minutes" class="block text-sm font-medium text-stone-800">Slot length (minutes)</label>
                <input type="number" id="slot_duration_minutes" name="slot_duration_minutes" min="10" max="240" required
                    value="{{ old('slot_duration_minutes', $settings->slot_duration_minutes) }}"
                    class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="buffer_minutes" class="block text-sm font-medium text-stone-800">Buffer after each slot (minutes)</label>
                <input type="number" id="buffer_minutes" name="buffer_minutes" min="0" max="120" required
                    value="{{ old('buffer_minutes', $settings->buffer_minutes) }}"
                    class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="min_notice_hours" class="block text-sm font-medium text-stone-800">Minimum notice (hours)</label>
                <input type="number" id="min_notice_hours" name="min_notice_hours" min="0" max="168" required
                    value="{{ old('min_notice_hours', $settings->min_notice_hours) }}"
                    class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="max_advance_days" class="block text-sm font-medium text-stone-800">How far ahead (days)</label>
                <input type="number" id="max_advance_days" name="max_advance_days" min="1" max="90" required
                    value="{{ old('max_advance_days', $settings->max_advance_days) }}"
                    class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
        </div>

        @php($tzVal = old('timezone', $settings->timezone))
        <div>
            <label for="timezone" class="block text-sm font-medium text-stone-800">Timezone for your schedule (optional)</label>
            <select id="timezone" name="timezone" class="mt-1 w-full max-w-2xl rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                @foreach ($timezoneChoices as $opt)
                    <option value="{{ $opt['id'] }}" @selected((string) $tzVal === (string) $opt['id'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-stone-500">GMT labels are a guide; many regions use daylight saving — the list uses standard city timezones. Leave on default to follow your profile or the app.</p>
        </div>

        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">Save settings</button>
    </form>

    <div class="mt-10 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-stone-900">Weekly hours</h3>
        <p class="mt-1 text-sm text-stone-600">Add one or more blocks per weekday (coach local time).</p>

        <ul class="mt-4 divide-y divide-stone-100 rounded-xl border border-stone-100">
            @forelse ($availability as $row)
                <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm">
                    <span>
                        {{ $weekdayLabels[$row->day_of_week] ?? $row->day_of_week }}
                        · {{ \Illuminate\Support\Str::substr($row->start_time, 0, 5) }} – {{ \Illuminate\Support\Str::substr($row->end_time, 0, 5) }}
                    </span>
                    <form method="post" action="{{ route('coach.booking.availability.destroy', [$tenant, $row]) }}" class="inline" onsubmit="return confirm('Remove this block?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-red-700 hover:underline">Remove</button>
                    </form>
                </li>
            @empty
                <li class="px-3 py-6 text-center text-sm text-stone-500">No weekly blocks yet.</li>
            @endforelse
        </ul>

        <form method="post" action="{{ route('coach.booking.availability.store', $tenant) }}" class="mt-6 space-y-4 rounded-xl bg-stone-50 p-4">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="day_of_week" class="block text-xs font-medium text-stone-600">Day</label>
                    <select id="day_of_week" name="day_of_week" class="mt-1 w-full rounded-lg border border-stone-200 px-2 py-2 text-sm">
                        @foreach ($weekdayLabels as $i => $label)
                            <option value="{{ $i }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="start_time" class="block text-xs font-medium text-stone-600">Start</label>
                    <input type="time" id="start_time" name="start_time" required value="09:00"
                        class="mt-1 w-full rounded-lg border border-stone-200 px-2 py-2 text-sm">
                </div>
                <div>
                    <label for="end_time" class="block text-xs font-medium text-stone-600">End</label>
                    <input type="time" id="end_time" name="end_time" required value="17:00"
                        class="mt-1 w-full rounded-lg border border-stone-200 px-2 py-2 text-sm">
                </div>
            </div>
            <button type="submit" class="pc-btn-primary pc-ring-focus inline-flex w-full appearance-none items-center justify-center rounded-full border border-transparent px-4 py-2.5 text-sm font-semibold text-white shadow-sm sm:w-auto sm:min-w-[10rem]">
                Add block
            </button>
        </form>
    </div>
@endsection
