@extends('layouts.app')

@push('head')
@php($primary = $tenant->branding['primary'] ?? '#0d9488')
@php($accent = $tenant->branding['accent'] ?? '#0f766e')
<style>:root { --pc-brand: {{ $primary }}; --pc-brand-accent: {{ $accent }}; } .pc-brand-ring:focus { --tw-ring-color: var(--pc-brand); } .pc-brand-brd:focus { border-color: var(--pc-brand); } .pc-btn { background-color: var(--pc-brand); } .pc-btn:hover { filter: brightness(0.92); } .pc-text { color: var(--pc-brand); }</style>
@endpush

@section('title', 'Sign in · '.$tenant->name)

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-stone-900">Sign in</h1>
        <p class="mt-1 text-sm text-stone-500">{{ $tenant->name }}</p>
        <p class="mt-2 text-xs text-stone-500">
            <a href="{{ route('login') }}" class="font-medium text-teal-700 hover:underline" title="Same email and password as the main sign-in; from this page you land in this space after logging in.">Main sign in</a>
        </p>

        <x-google-oauth-button :tenant="$tenant" margin="mt-6" label="Continue with Google" />
        @if (filled(config('services.google.client_id')))
            <p class="mt-4 text-center text-xs text-stone-500">or use your email</p>
        @endif

        <form method="POST" action="{{ route('space.login', $tenant) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:pc-brand-brd focus:outline-none focus:ring-1 pc-brand-ring">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-stone-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:pc-brand-brd focus:outline-none focus:ring-1 pc-brand-ring">
            </div>
            <div class="flex items-center gap-2">
                <input id="remember" type="checkbox" name="remember" class="rounded border-stone-300 text-teal-600">
                <label for="remember" class="text-sm text-stone-600">Remember me</label>
            </div>
            <button type="submit" class="pc-btn w-full rounded-full py-2.5 text-sm font-medium text-white">Sign in</button>
        </form>
        <p class="mt-6 text-center text-sm text-stone-600">
            New here?
            <a href="{{ route('space.register', $tenant) }}" class="font-medium pc-text hover:underline">Create an account</a>
        </p>
        <p class="mt-5 border-t border-stone-100 pt-5 text-center text-xs text-stone-500">
            Only need a session?
            <a href="{{ route('public.book', $tenant) }}" class="font-medium text-teal-800 hover:underline">Book a coach</a>
            — no account required (we ask for contact details).
        </p>
    </div>
@endsection
