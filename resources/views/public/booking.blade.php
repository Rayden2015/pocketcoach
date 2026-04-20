@extends('layouts.app')

@php
    use Illuminate\Support\Carbon;
    $authUser = auth()->user();
    $groupedSlots = collect($slots)->groupBy(fn (array $s) => Carbon::parse($s['start_local'])->toDateString());
@endphp

@section('title', $tenant->name.' — book a session')

@section('content')
    <div class="mx-auto max-w-2xl">
        <nav class="mb-4 text-xs font-medium text-stone-500" aria-label="Breadcrumb">
            <a href="{{ route('public.catalog', $tenant) }}" class="text-teal-800 hover:underline">{{ $tenant->name }}</a>
            <span class="mx-1.5 text-stone-300" aria-hidden="true">/</span>
            <span class="text-stone-700">Book a session</span>
        </nav>
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Book a coach</h1>
        <p class="mt-2 text-sm text-stone-600">
            Pick a coach and an open time. Pending requests hold the slot until the coach responds.
            <a href="{{ route('public.catalog', $tenant) }}" class="font-medium text-teal-800 underline decoration-teal-800/30 hover:decoration-teal-800">Back to catalog</a>
        </p>

        @if ($coaches->isEmpty())
            <div class="mt-8 space-y-4 rounded-2xl border border-amber-200/90 bg-amber-50/80 px-5 py-6 text-sm text-stone-800">
                <p class="font-semibold text-stone-900">No bookable coaches yet</p>
                <p class="text-stone-700">
                    Public booking only appears after a <strong>coach or admin</strong> in this space turns it on and adds weekly hours.
                    Until then, visitors will see this message.
                </p>
                <ol class="list-decimal space-y-2 pl-5 text-stone-700">
                    <li>Sign in with a staff account (owner, admin, or instructor) for this space.</li>
                    <li>Open <strong>Coach console</strong> → <strong>Booking setup</strong>.</li>
                    <li>Turn on <strong>Accept public bookings</strong>, set slot length and notice window, then add at least one <strong>weekly hours</strong> block (for example Mon–Fri 9:00–17:00).</li>
                </ol>
                @if ($staffCanConfigureBooking)
                    <div class="flex flex-wrap gap-2 pt-2">
                        <a href="{{ route('coach.booking.settings', $tenant) }}" class="inline-flex items-center rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">Open booking setup</a>
                        <a href="{{ route('coach.bookings.index', $tenant) }}" class="inline-flex items-center rounded-full border border-stone-300 bg-white px-5 py-2.5 text-sm font-medium text-stone-800 hover:bg-stone-50">View booking requests</a>
                    </div>
                @else
                    <p class="text-xs text-stone-600">
                        Not a coach? Ask the space owner to enable booking, or share this page: <span class="break-all font-mono text-stone-800">{{ $tenant->publicUrl('book') }}</span>
                    </p>
                @endif
            </div>
        @else
            <form method="get" action="{{ route('public.book', $tenant) }}" class="mt-8 space-y-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Step 1 — availability</p>
                <div>
                    <label for="coach" class="block text-sm font-medium text-stone-800">Coach</label>
                    <select id="coach" name="coach" class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" required>
                        <option value="" disabled {{ $selectedCoachId === 0 ? 'selected' : '' }}>Select a coach…</option>
                        @foreach ($coaches as $c)
                            <option value="{{ $c->id }}" @selected((int) $selectedCoachId === (int) $c->id)>
                                {{ $c->name ?: $c->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="from" class="block text-sm font-medium text-stone-800">From (optional)</label>
                        <input type="date" id="from" name="from" value="{{ request('from') }}"
                            class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="to" class="block text-sm font-medium text-stone-800">To (optional)</label>
                        <input type="date" id="to" name="to" value="{{ request('to') }}"
                            class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                </div>
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">Show open times</button>
            </form>

            @if ($selectedCoachId > 0)
                @if ($slots === [])
                    <p class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950">
                        No open times in this range. Try other dates, or the coach may still be setting up weekly hours.
                    </p>
                @else
                    <form method="post" action="{{ route('public.book.store', $tenant) }}" class="mt-8 space-y-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm" id="booking-form">
                        @csrf
                        <input type="hidden" name="coach_user_id" value="{{ $selectedCoachId }}">
                        <input type="hidden" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" required>
                        <input type="hidden" name="ends_at" id="ends_at" value="{{ old('ends_at') }}" required>

                        <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Step 2 — time</p>
                        <fieldset class="space-y-6">
                            <legend class="sr-only">Choose a time slot</legend>
                            @foreach ($groupedSlots as $date => $daySlots)
                                <div>
                                    <p class="text-sm font-semibold text-stone-900">{{ Carbon::parse($date)->format('l, M j') }}</p>
                                    <ul class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($daySlots as $s)
                                            @php
                                                $sid = 'slot-'.md5($s['start'].$s['end']);
                                            @endphp
                                            <li>
                                                <input type="radio" name="slot_pick" id="{{ $sid }}" class="peer sr-only js-slot-pick"
                                                    data-start="{{ $s['start'] }}" data-end="{{ $s['end'] }}"
                                                    {{ old('starts_at') === $s['start'] && old('ends_at') === $s['end'] ? 'checked' : '' }}>
                                                <label for="{{ $sid }}"
                                                    class="block cursor-pointer rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-xs font-medium text-stone-800 peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-900 hover:border-stone-300">
                                                    {{ Carbon::parse($s['start_local'])->format('g:i A') }}
                                                    <span class="text-stone-400">–</span>
                                                    {{ Carbon::parse($s['end_local'])->format('g:i A') }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </fieldset>

                        @guest
                            <div class="space-y-3 border-t border-stone-100 pt-6">
                                <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Your contact details</p>
                                <div>
                                    <label for="guest_name" class="block text-sm font-medium text-stone-800">Name</label>
                                    <input type="text" id="guest_name" name="guest_name" required
                                        value="{{ old('guest_name') }}"
                                        class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                </div>
                                <div>
                                    <label for="guest_email" class="block text-sm font-medium text-stone-800">Email</label>
                                    <input type="email" id="guest_email" name="guest_email" required autocomplete="email"
                                        value="{{ old('guest_email') }}"
                                        class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                </div>
                                <div>
                                    <label for="guest_phone" class="block text-sm font-medium text-stone-800">Phone</label>
                                    <input type="tel" id="guest_phone" name="guest_phone" required autocomplete="tel"
                                        value="{{ old('guest_phone') }}"
                                        class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                </div>
                            </div>
                        @else
                            <p class="border-t border-stone-100 pt-6 text-sm text-stone-600">
                                Signed in as <span class="font-medium text-stone-900">{{ $authUser->email }}</span>. We&rsquo;ll attach this booking to your account.
                            </p>
                        @endguest

                        <div>
                            <label for="booker_message" class="block text-sm font-medium text-stone-800">Message (optional)</label>
                            <textarea id="booker_message" name="booker_message" rows="3" maxlength="2000"
                                class="mt-1 w-full rounded-xl border border-stone-200 px-3 py-2 text-sm shadow-inner focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="What would you like to focus on?">{{ old('booker_message') }}</textarea>
                        </div>

                        <button type="submit" class="w-full rounded-full bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 sm:w-auto">
                            Request this time
                        </button>
                    </form>

                    @push('head')
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var starts = document.getElementById('starts_at');
                                var ends = document.getElementById('ends_at');
                                function applyPick(el) {
                                    if (!el || !starts || !ends) return;
                                    starts.value = el.getAttribute('data-start') || '';
                                    ends.value = el.getAttribute('data-end') || '';
                                }
                                document.querySelectorAll('.js-slot-pick').forEach(function (radio) {
                                    radio.addEventListener('change', function () {
                                        if (radio.checked) applyPick(radio);
                                    });
                                });
                                var checked = document.querySelector('.js-slot-pick:checked');
                                if (checked) applyPick(checked);
                                var form = document.getElementById('booking-form');
                                if (form) {
                                    form.addEventListener('submit', function (e) {
                                        if (!starts.value || !ends.value) {
                                            e.preventDefault();
                                            alert('Please choose a time slot.');
                                        }
                                    });
                                }
                            });
                        </script>
                    @endpush
                @endif
            @endif
        @endif
    </div>
@endsection
