@extends('layouts.app')

@push('head')
@php($primary = $tenant->branding['primary'] ?? '#0d9488')
@php($accent = $tenant->branding['accent'] ?? '#0f766e')
<style>:root { --pc-brand: {{ $primary }}; --pc-brand-accent: {{ $accent }}; } .pc-brand-ring:focus { --tw-ring-color: var(--pc-brand); } .pc-brand-brd:focus { border-color: var(--pc-brand); } .pc-btn { background-color: var(--pc-brand); } .pc-btn:hover { filter: brightness(0.92); } .pc-text { color: var(--pc-brand); }</style>
@endpush

@section('title', 'Join '.$tenant->name)

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        @if (! empty($tenant->branding['welcome_headline']))
            <p class="text-sm font-medium pc-text">{{ $tenant->branding['welcome_headline'] }}</p>
        @endif
        <h1 class="mt-1 text-xl font-semibold text-stone-900">Create your account</h1>
        <p class="mt-1 text-sm text-stone-500">Space: {{ $tenant->name }} · <code class="text-xs">{{ $tenant->slug }}</code></p>

        <x-google-oauth-button :tenant="$tenant" margin="mt-6" label="Continue with Google" />
        @if (filled(config('services.google.client_id')))
            <p class="mt-4 text-center text-xs text-stone-500">or register with email</p>
        @endif

        <form method="POST" action="{{ route('space.register', $tenant) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-stone-700">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:pc-brand-brd focus:outline-none focus:ring-1 pc-brand-ring">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:pc-brand-brd focus:outline-none focus:ring-1 pc-brand-ring">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-stone-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:pc-brand-brd focus:outline-none focus:ring-1 pc-brand-ring">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:pc-brand-brd focus:outline-none focus:ring-1 pc-brand-ring">
            </div>
            <button type="submit" class="pc-btn w-full rounded-full py-2.5 text-sm font-medium text-white">Register</button>
        </form>
        <p class="mt-6 text-center text-sm text-stone-600">
            Already have an account?
            <a href="{{ route('space.login', $tenant) }}" class="font-medium pc-text hover:underline">Log in</a>
        </p>
    </div>
@endsection
