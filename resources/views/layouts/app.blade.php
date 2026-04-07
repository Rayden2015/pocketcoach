<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: "Plus Jakarta Sans", system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <header class="sticky top-0 z-50 border-b border-stone-200/90 bg-white/95 shadow-sm backdrop-blur-md supports-[backdrop-filter]:bg-white/80">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-3.5">
            <a href="{{ auth()->check() ? route('my-learning') : route('home') }}" class="shrink-0 text-lg font-bold tracking-tight text-stone-900">
                {{ config('app.name') }}
            </a>
            @auth
                <form method="GET" action="{{ route('search.courses') }}" class="order-3 flex min-w-0 flex-1 basis-full items-center gap-2 sm:order-none sm:basis-[14rem] md:basis-72">
                    <input type="search" name="q" value="{{ request()->routeIs('search.*') ? request('q') : '' }}" placeholder="Search your courses…" autocomplete="off"
                        class="min-w-0 flex-1 rounded-full border border-stone-300 bg-stone-50/80 px-4 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20">
                    <button type="submit" class="shrink-0 rounded-full bg-stone-200 px-3 py-2 text-xs font-semibold text-stone-800 hover:bg-stone-300 sm:text-sm">Search</button>
                </form>
            @endauth
            <nav class="ml-auto flex flex-wrap items-center gap-2 text-sm font-medium text-stone-600 md:gap-3">
                @auth
                    <a href="{{ route('my-learning') }}"
                        class="inline-flex items-center rounded-full bg-stone-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-stone-800">
                        My learning
                    </a>
                    @if(auth()->user()->is_super_admin)
                        <a href="{{ route('platform.tenants.index') }}" class="rounded-full px-2 py-1.5 hover:bg-stone-100 hover:text-stone-900">Platform</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="max-w-[10rem] truncate rounded-full px-2 py-1.5 hover:bg-stone-100 hover:text-stone-900 md:max-w-[12rem]" title="{{ auth()->user()->email }}">
                        {{ auth()->user()->name ?: auth()->user()->email }}
                    </a>
                    <a href="{{ route('dashboard') }}" class="rounded-full px-2 py-1.5 hover:bg-stone-100 hover:text-stone-900">Profile &amp; spaces</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full px-2 py-1.5 text-stone-500 hover:bg-stone-100 hover:text-stone-800">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2 hover:bg-stone-100 hover:text-stone-900">Log in</a>
                    <a href="{{ route('create-space') }}" class="rounded-full border border-stone-300 bg-white px-4 py-2 shadow-sm hover:border-stone-400 hover:text-stone-900">Create a space</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @auth
            @isset($tenant)
                @php($tenantMembership = auth()->user()->memberships()->where('tenant_id', $tenant->id)->first())
                <div class="mb-6 rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm shadow-sm">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        @if (filled(auth()->user()->name) && auth()->user()->name !== auth()->user()->email)
                            <span class="font-medium text-stone-900">{{ auth()->user()->name }}</span>
                            <span class="text-stone-500">{{ auth()->user()->email }}</span>
                        @else
                            <span class="font-medium text-stone-900">{{ auth()->user()->email }}</span>
                        @endif
                        @if ($tenantMembership)
                            <span class="inline-flex rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-900">Role in {{ $tenant->name }}: {{ $tenantMembership->role }}</span>
                        @else
                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-950">No space membership yet — use <strong>Join this space</strong> on the catalog if you are a learner.</span>
                        @endif
                        @if (auth()->user()->is_super_admin)
                            <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-900">Platform: super admin</span>
                        @endif
                        <a href="{{ route('dashboard') }}" class="text-teal-700 hover:underline">All spaces &amp; account details →</a>
                    </div>
                </div>
            @endisset
        @endauth

        @if (session('status'))
            <p class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
        @endif

        @if (session('warning'))
            <p class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">{{ session('warning') }}</p>
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
