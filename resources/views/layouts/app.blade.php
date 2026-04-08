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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.design-tokens')
    @stack('head')
</head>
<body class="pc-shell font-sans antialiased text-slate-900">
    <header class="pc-header-glass sticky top-0 z-50 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-3.5">
            <a href="{{ auth()->check() ? route('my-learning') : route('home') }}" class="shrink-0 text-lg font-bold tracking-tight text-[var(--pc-brand)]">
                {{ config('app.name') }}
            </a>
            @auth
                <form method="GET" action="{{ route('search.courses') }}" class="order-3 flex min-w-0 flex-1 basis-full items-center gap-2 sm:order-none sm:basis-[14rem] md:basis-72">
                    <input type="search" name="q" value="{{ request()->routeIs('search.*') ? request('q') : '' }}" placeholder="Search your courses…" autocomplete="off"
                        class="pc-ring-focus min-w-0 flex-1 rounded-full border border-slate-200/90 bg-white/90 px-4 py-2 text-sm text-slate-900 shadow-inner placeholder:text-slate-400 focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                    <button type="submit" class="pc-btn-primary shrink-0 rounded-full px-4 py-2 text-xs font-semibold shadow-sm sm:text-sm">Search</button>
                </form>
            @endauth
            <nav class="ml-auto flex flex-wrap items-center gap-1.5 text-sm font-medium text-slate-600 md:gap-2">
                @auth
                    <a href="{{ route('my-learning') }}"
                        class="inline-flex items-center rounded-full bg-[var(--pc-brand)] px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:brightness-110">
                        My learning
                    </a>
                    @if(auth()->user()->is_super_admin)
                        <a href="{{ route('platform.tenants.index') }}" class="rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)]">Platform</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="max-w-[10rem] truncate rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)] md:max-w-[12rem]" title="{{ auth()->user()->email }}">
                        {{ auth()->user()->name ?: auth()->user()->email }}
                    </a>
                    <a href="{{ route('dashboard') }}" class="hidden rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)] sm:inline">Profile</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full px-3 py-2 text-slate-500 hover:bg-white/80 hover:text-slate-800">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)]">Log in</a>
                    <a href="{{ route('create-space') }}" class="rounded-full border border-slate-200/80 bg-white/90 px-4 py-2 shadow-sm hover:border-[var(--pc-accent)] hover:text-[var(--pc-brand)]">Create a space</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @auth
            @isset($tenant)
                @php($tenantMembership = auth()->user()->memberships()->where('tenant_id', $tenant->id)->first())
                <div class="mb-8 rounded-2xl border border-white/60 bg-white/85 px-5 py-4 shadow-[var(--pc-shadow)] backdrop-blur-sm sm:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1 space-y-3">
                            @if (filled(auth()->user()->name) && auth()->user()->name !== auth()->user()->email)
                                <p class="text-lg font-semibold leading-tight text-[var(--pc-brand)]">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-slate-600 break-all">{{ auth()->user()->email }}</p>
                            @else
                                <p class="text-lg font-semibold leading-tight text-[var(--pc-brand)] break-all">{{ auth()->user()->email }}</p>
                            @endif
                            <div class="flex flex-wrap gap-2">
                                @if ($tenantMembership)
                                    <span class="inline-flex items-center rounded-full bg-[color-mix(in_srgb,var(--pc-accent)_16%,white)] px-3 py-1 text-xs font-semibold text-[var(--pc-brand)] ring-1 ring-[color-mix(in_srgb,var(--pc-accent)_35%,transparent)]">
                                        {{ ucfirst($tenantMembership->role) }} · {{ $tenant->name }}
                                    </span>
                                @else
                                    <span class="inline-flex max-w-prose rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-950 ring-1 ring-amber-200/80">
                                        Not a member of this space yet — use <strong class="font-semibold">Join this space</strong> on the catalog when you are learning here.
                                    </span>
                                @endif
                                @if (auth()->user()->is_super_admin)
                                    <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-900 ring-1 ring-violet-200">Platform admin</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[var(--pc-brand)] shadow-sm transition hover:border-[var(--pc-accent)] hover:bg-slate-50">
                            Account &amp; spaces →
                        </a>
                    </div>
                </div>
            @endisset
        @endauth

        @if (session('status'))
            <p class="mb-6 rounded-2xl border border-[color-mix(in_srgb,var(--pc-accent)_28%,#99f6e4)] bg-[color-mix(in_srgb,var(--pc-accent)_12%,white)] px-4 py-3 text-sm text-slate-800 shadow-sm">{{ session('status') }}</p>
        @endif

        @if (session('warning'))
            <p class="mb-6 rounded-2xl border border-amber-200/90 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm">{{ session('warning') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm">
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
