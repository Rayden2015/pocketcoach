<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#fafaf9] text-stone-900 antialiased">
    <header class="border-b border-stone-200/80 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/70">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="text-lg font-semibold tracking-tight text-teal-800">
                {{ config('app.name') }}
            </a>
            <nav class="flex flex-wrap items-center gap-4 text-sm font-medium text-stone-600">
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-teal-700">Spaces</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-stone-500 hover:text-teal-700">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-teal-700">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-teal-600 px-4 py-1.5 text-white hover:bg-teal-700">Register</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-8">
        @if (session('status'))
            <p class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
