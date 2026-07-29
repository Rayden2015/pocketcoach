@extends('layouts.app')

@section('title', 'Coach invitation — '.$tenant->name)

@section('content')
    <div class="mx-auto max-w-lg">
        <p class="text-xs font-medium uppercase tracking-wide text-teal-700">{{ $tenant->name }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-stone-900">Coach invitation</h1>

        @if (session('warning'))
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ session('warning') }}</p>
        @endif

        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-stone-700">
                You were invited as
                <strong>{{ $invite->role === 'admin' ? 'admin' : 'instructor' }}</strong>
                (expires {{ $invite->expires_at->timezone(config('app.timezone'))->format('M j, Y') }}).
            </p>

            @if ($mode === 'wrong_user')
                <p class="mt-4 text-sm text-red-700">
                    You are signed in as {{ auth()->user()->email }}, but this invite is for {{ $invite->email }}.
                    <form method="POST" action="{{ route('logout') }}" class="mt-3 inline">
                        @csrf
                        <button type="submit" class="font-medium text-teal-700 hover:underline">Sign out</button>
                    </form>
                    and open the invite link again.
                </p>
            @elseif ($mode === 'confirm')
                <form method="POST" action="{{ route('space.coach-invite.accept', [$tenant, $invite->token]) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        Join {{ $tenant->name }} as {{ $invite->role }}
                    </button>
                </form>
            @elseif ($mode === 'login')
                <p class="mt-3 text-sm text-stone-600">You already have an account. Enter your password to join the team.</p>
                <form method="POST" action="{{ route('space.coach-invite.accept', [$tenant, $invite->token]) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-stone-700">Email</label>
                        <input type="email" value="{{ $invite->email }}" disabled
                            class="mt-1 w-full rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-600">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-stone-700">Password</label>
                        <input id="password" name="password" type="password" required
                            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Sign in &amp; join</button>
                </form>
            @else
                <p class="mt-3 text-sm text-stone-600">Create your account to join the coaching team.</p>
                <form method="POST" action="{{ route('space.coach-invite.accept', [$tenant, $invite->token]) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-stone-700">Email</label>
                        <input type="email" value="{{ $invite->email }}" disabled
                            class="mt-1 w-full rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-600">
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-stone-700">Your name</label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}"
                            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-stone-700">Password</label>
                        <input id="password" name="password" type="password" required
                            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">Create account &amp; join</button>
                </form>
            @endif
        </div>
    </div>
@endsection
