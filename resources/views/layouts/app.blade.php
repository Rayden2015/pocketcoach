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
            <a href="{{ auth()->check() ? (auth()->user()->coachesAnySpace() ? route('my-coaching') : route('my-learning')) : route('home') }}" class="shrink-0 text-lg font-bold tracking-tight text-[var(--pc-brand)]">
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
                    @php($navActive = 'inline-flex items-center rounded-full bg-[var(--pc-brand)] px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:brightness-110')
                    @php($navIdle = 'rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)]')
                    <a href="{{ route('my-learning') }}" class="{{ request()->routeIs('my-learning') ? $navActive : $navIdle }}">
                        My learning
                    </a>
                    @if(auth()->user()->coachesAnySpace())
                        <a href="{{ route('my-coaching') }}" class="{{ request()->routeIs('my-coaching') ? $navActive : $navIdle }}">
                            My coaching
                        </a>
                    @endif
                    @if(auth()->user()->is_super_admin)
                        <a href="{{ route('platform.tenants.index') }}" class="rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)]">Platform</a>
                    @endif
                    <div id="pc-notifications-bell" class="relative shrink-0" data-unread-url="{{ route('notifications.unread-count') }}" data-list-url="{{ route('notifications.index') }}" data-read-all-url="{{ route('notifications.read-all') }}" data-read-one-base="{{ url('/notifications') }}">
                        <button type="button" class="pc-ring-focus relative inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 hover:bg-white/80 hover:text-[var(--pc-brand)]" aria-label="Notifications" aria-expanded="false" aria-haspopup="true" data-bell-toggle>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                            <span class="pointer-events-none absolute -right-0.5 -top-0.5 hidden h-5 min-w-5 items-center justify-center rounded-full bg-[var(--pc-accent)] px-1 text-[10px] font-bold leading-none text-white shadow-sm" data-unread-badge></span>
                        </button>
                        <div class="absolute right-0 z-[60] mt-2 hidden w-[min(22rem,calc(100vw-2rem))] origin-top-right rounded-2xl border border-slate-200/90 bg-white/95 py-2 shadow-xl backdrop-blur-sm" data-bell-panel hidden role="menu">
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3 pb-2">
                                <p class="text-sm font-semibold text-slate-800">Notifications</p>
                                <button type="button" class="text-xs font-semibold text-[var(--pc-accent)] hover:underline disabled:opacity-40" data-mark-all-read>Mark all as read</button>
                            </div>
                            <ul class="max-h-[min(24rem,70vh)] overflow-y-auto" data-bell-list role="none"></ul>
                            <p class="hidden px-3 py-6 text-center text-sm text-slate-500" data-bell-empty>You're all caught up.</p>
                        </div>
                    </div>
                    <a href="{{ route('profile') }}" class="inline-flex max-w-[min(14rem,calc(100vw-10rem))] items-center gap-2 truncate rounded-full px-3 py-2 text-slate-700 hover:bg-white/80 hover:text-[var(--pc-brand)] md:max-w-[16rem]" title="{{ auth()->user()->email }}" aria-label="Profile and account settings">
                        <svg class="h-5 w-5 shrink-0 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <span class="truncate">{{ auth()->user()->name ?: auth()->user()->email }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full px-3 py-2 text-slate-500 hover:bg-white/80 hover:text-slate-800">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)]">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-full px-3 py-2 hover:bg-white/80 hover:text-[var(--pc-brand)]">Sign up</a>
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
                                    <span class="inline-flex max-w-prose rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-950 ring-1 ring-amber-200/80" title="Open the learner catalog for this space and use Join this space when you want to learn here.">
                                        Not a member yet — join from the catalog.
                                    </span>
                                @endif
                                @if (auth()->user()->is_super_admin)
                                    <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-900 ring-1 ring-violet-200">Platform admin</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a href="{{ route('profile') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[var(--pc-brand)] shadow-sm transition hover:border-[var(--pc-accent)] hover:bg-slate-50">
                                Profile
                            </a>
                            <a href="{{ route('my-coaching') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[var(--pc-brand)] shadow-sm transition hover:border-[var(--pc-accent)] hover:bg-slate-50">
                                My coaching
                            </a>
                        </div>
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
