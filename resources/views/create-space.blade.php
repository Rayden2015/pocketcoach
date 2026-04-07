@extends('layouts.app')

@section('title', 'Create your coaching space')

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-stone-900">Create your space</h1>
        <p class="mt-2 text-sm text-stone-600">
            You’ll get a shareable home for learners at
            <code class="rounded bg-stone-100 px-1 py-0.5 text-xs">{{ url('/your-slug') }}</code>
            (registration, catalog, and lessons for your brand).
        </p>

        <form method="POST" action="{{ route('create-space') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label for="space_name" class="block text-sm font-medium text-stone-700">Space / business name</label>
                <input id="space_name" type="text" name="space_name" value="{{ old('space_name') }}" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    placeholder="e.g. Adeola Coaching">
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-stone-700">URL slug</label>
                <p class="text-xs text-stone-500">Lowercase letters, numbers, hyphens. Shown in links: /your-slug/register</p>
                <input id="slug" type="text" name="slug" value="{{ old('slug') }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    placeholder="adeola-coaching">
            </div>
            <div>
                <label for="welcome_headline" class="block text-sm font-medium text-stone-700">Welcome headline (optional)</label>
                <input id="welcome_headline" type="text" name="welcome_headline" value="{{ old('welcome_headline') }}"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    placeholder="Shown at the top of your registration page">
            </div>
            <div>
                <label for="primary_color" class="block text-sm font-medium text-stone-700">Primary color (optional)</label>
                <input id="primary_color" type="text" name="primary_color" value="{{ old('primary_color', '#0d9488') }}"
                    pattern="^#[0-9A-Fa-f]{6}$"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-sm text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>

            <div class="border-t border-stone-200 pt-6">
                <h2 class="text-sm font-medium text-stone-900">Your owner account</h2>
                <p class="mt-1 text-xs text-stone-500">You’ll be signed in as <strong>owner</strong> for this space.</p>
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-stone-700">Your name</label>
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

            <button type="submit" class="w-full rounded-full bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Create space &amp; continue</button>
        </form>
    </div>
@endsection
