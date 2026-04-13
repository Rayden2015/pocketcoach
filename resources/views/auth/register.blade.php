@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold">Create account</h1>
        <p class="mt-2 text-sm text-stone-600">You’ll join specific spaces from their catalog or register links. This creates your Pocket Coach account you can use everywhere.</p>
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif
        <x-google-oauth-button label="Sign up with Google" />
        @if (filled(config('services.google.client_id')))
            <p class="mt-4 text-center text-xs text-stone-500">or register with email</p>
        @endif
        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-stone-700">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-stone-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-stone-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <button type="submit" class="w-full rounded-full bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Register</button>
        </form>
        <p class="mt-6 text-center text-sm text-stone-600">
            Already registered?
            <a href="{{ route('login') }}" class="font-medium text-teal-700 hover:underline">Log in</a>
        </p>
    </div>
@endsection
