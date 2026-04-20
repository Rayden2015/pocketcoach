@extends('layouts.app')

@php
    use App\Enums\BookingStatus;
@endphp

@section('title', $tenant->name.' — bookings')

@section('content')
    @include('coach.partials.header')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-stone-600">Incoming requests and your schedule. Pending slots stay blocked for others.</p>
        <a href="{{ route('coach.booking.settings', $tenant) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-medium text-stone-800 hover:bg-stone-50">Booking setup</a>
    </div>

    <ul class="mt-2 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white shadow-sm">
        @forelse ($bookings as $booking)
            <li class="px-4 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 space-y-1">
                        <p class="text-sm font-semibold text-stone-900">
                            {{ $booking->starts_at->timezone(config('app.timezone'))->format('D, M j Y · g:i A') }}
                            <span class="font-normal text-stone-500">– {{ $booking->ends_at->timezone(config('app.timezone'))->format('g:i A') }}</span>
                        </p>
                        <p class="text-sm text-stone-700">
                            {{ $booking->bookerDisplayName() }}
                            @if ($booking->bookerContactEmail())
                                <span class="text-stone-500">·</span> <span class="break-all">{{ $booking->bookerContactEmail() }}</span>
                            @endif
                            @if ($booking->guest_phone)
                                <span class="text-stone-500">·</span> {{ $booking->guest_phone }}
                            @endif
                        </p>
                        @if ($booking->booker_message)
                            <p class="text-sm text-stone-600">&ldquo;{{ $booking->booker_message }}&rdquo;</p>
                        @endif
                        <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                            {{ str_replace('_', ' ', $booking->status->value) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        @if ($booking->status === BookingStatus::Pending)
                            <form method="post" action="{{ route('coach.bookings.confirm', [$tenant, $booking]) }}" class="inline">
                                @csrf
                                <button type="submit" class="rounded-full bg-teal-600 px-4 py-2 font-semibold text-white hover:bg-teal-700">Confirm</button>
                            </form>
                            <form method="post" action="{{ route('coach.bookings.decline', [$tenant, $booking]) }}" class="inline space-y-2">
                                @csrf
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="text" name="coach_internal_note" placeholder="Note (optional)" maxlength="2000"
                                        class="min-w-[12rem] rounded-full border border-stone-200 px-3 py-2 text-xs">
                                    <button type="submit" class="rounded-full border border-red-200 bg-red-50 px-4 py-2 font-semibold text-red-900 hover:bg-red-100">Decline</button>
                                </div>
                            </form>
                        @endif
                        @if (in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true))
                            <form method="post" action="{{ route('coach.bookings.cancel', [$tenant, $booking]) }}" class="inline" onsubmit="return confirm('Cancel this booking?');">
                                @csrf
                                <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 font-medium text-stone-800 hover:bg-stone-50">Cancel as coach</button>
                            </form>
                        @endif
                    </div>
                </div>
            </li>
        @empty
            <li class="px-4 py-10 text-center text-sm text-stone-500">No bookings yet.</li>
        @endforelse
    </ul>

    <div class="mt-6">
        {{ $bookings->links() }}
    </div>
@endsection
