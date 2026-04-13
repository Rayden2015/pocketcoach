@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold">Log in</h1>
        <p class="mt-2 text-sm text-stone-600">One account for all spaces.</p>
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif
        <x-google-oauth-button label="Continue with Google" />
        @if (filled(config('services.google.client_id')))
            <p class="mt-4 text-center text-xs text-stone-500">or use your email</p>
        @endif
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-stone-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div class="flex items-center gap-2">
                <input id="remember" type="checkbox" name="remember" class="rounded border-stone-300 text-teal-600">
                <label for="remember" class="text-sm text-stone-600">Remember me</label>
            </div>
            <button type="submit" class="w-full rounded-full bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Sign in</button>
        </form>
        <p class="mt-6 text-center text-sm text-stone-600">
            New here?
            <a href="{{ route('register') }}" class="font-medium text-teal-700 hover:underline">Create an account</a>
            <span class="text-stone-400">·</span>
            <a href="{{ route('home') }}" class="font-medium text-teal-700 hover:underline">Browse spaces</a>
        </p>
        <p class="mt-3 text-center text-xs text-stone-500">Invited to a specific space? You can also <a href="{{ route('join-help') }}" class="font-medium text-teal-700 hover:underline">open it from its link</a>.</p>
    </div>
@endsection
