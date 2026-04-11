@extends('layouts.app')

@section('title', 'Create another space')

@section('content')
    <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-stone-900">Create another coaching space</h1>
        <p class="mt-2 text-sm text-stone-600">
            You’ll be the <strong>owner</strong> on this space. Your account stays the same (
            <span class="break-all font-medium">{{ auth()->user()->email }}</span>).
        </p>

        <form method="POST" action="{{ route('spaces.store') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label for="space_name" class="block text-sm font-medium text-stone-700">Space / business name</label>
                <input id="space_name" type="text" name="space_name" value="{{ old('space_name') }}" required
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-stone-700">URL slug</label>
                <p class="text-xs text-stone-500">Shown in links: <code class="rounded bg-stone-100 px-1">/{{ old('slug', 'your-slug') }}/catalog</code></p>
                <input id="slug" type="text" name="slug" value="{{ old('slug') }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-sm text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="welcome_headline" class="block text-sm font-medium text-stone-700">Welcome headline (optional)</label>
                <input id="welcome_headline" type="text" name="welcome_headline" value="{{ old('welcome_headline') }}"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>
            <div>
                <label for="primary_color" class="block text-sm font-medium text-stone-700">Primary color (optional)</label>
                <input id="primary_color" type="text" name="primary_color" value="{{ old('primary_color', '#0d9488') }}"
                    pattern="^#[0-9A-Fa-f]{6}$"
                    class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-sm text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('my-coaching') }}" class="inline-flex items-center rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:border-teal-400">Cancel</a>
                <button type="submit" class="inline-flex rounded-full bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Create space</button>
            </div>
        </form>
    </div>
@endsection
