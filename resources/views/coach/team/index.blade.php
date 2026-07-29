@extends('layouts.app')

@section('title', $tenant->name.' — team')

@section('content')
    @include('coach.partials.header')

    @if (session('status'))
        <p class="mb-4 rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ session('status') }}</p>
    @endif
    @error('membership')
        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
    @enderror
    @error('role')
        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <div class="grid gap-8 lg:grid-cols-2">
        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-stone-900">Invite a coach</h2>
            <p class="mt-1 text-xs text-stone-500">They get an email with a link to join as instructor or admin and set their own password.</p>

            <form method="POST" action="{{ route('coach.team.invites.store', $tenant) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-stone-700">Role</label>
                    <select id="role" name="role"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        <option value="instructor" @selected(old('role', 'instructor') === 'instructor')>Instructor — coach tools &amp; bookings</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin — coach tools + manage team</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Send invitation</button>
            </form>
        </section>

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-stone-900">Pending invitations</h2>
            <ul class="mt-3 divide-y divide-stone-100">
                @forelse ($pendingInvites as $invite)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <div>
                            <p class="font-medium text-stone-900">{{ $invite->email }}</p>
                            <p class="text-xs text-stone-500">{{ ucfirst($invite->role) }} · expires {{ $invite->expires_at->timezone(config('app.timezone'))->format('M j, Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('coach.team.invites.destroy', [$tenant, $invite]) }}" onsubmit="return confirm('Cancel this invitation?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">Cancel</button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-sm text-stone-500">No pending invitations.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="mt-8 rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="border-b border-stone-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-stone-900">Coaching team</h2>
        </div>
        <ul class="divide-y divide-stone-100">
            @foreach ($staff as $membership)
                <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 text-sm">
                    <div>
                        <p class="font-medium text-stone-900">{{ $membership->user?->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-stone-500">{{ $membership->user?->email }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($membership->role === 'owner')
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-900">Owner</span>
                        @else
                            <form method="POST" action="{{ route('coach.team.members.update', [$tenant, $membership]) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <select name="role" class="rounded-lg border border-stone-300 px-2 py-1 text-xs"
                                    @disabled((int) $membership->user_id === (int) auth()->id())
                                    onchange="this.form.submit()">
                                    <option value="instructor" @selected($membership->role === 'instructor')>Instructor</option>
                                    <option value="admin" @selected($membership->role === 'admin')>Admin</option>
                                </select>
                            </form>
                            @if ((int) $membership->user_id !== (int) auth()->id())
                                <form method="POST" action="{{ route('coach.team.members.destroy', [$tenant, $membership]) }}" onsubmit="return confirm('Remove this person from the coaching team?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
