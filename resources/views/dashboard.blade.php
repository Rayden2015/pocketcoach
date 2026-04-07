@extends('layouts.app')

@section('title', 'Your spaces')

@section('content')
    <section class="mb-10 flex flex-col gap-4 rounded-2xl bg-gradient-to-br from-stone-900 to-teal-950 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-300/90">Learner hub</p>
            <h2 class="mt-1 text-xl font-bold tracking-tight">Continue all your courses in one place</h2>
            <p class="mt-1 max-w-xl text-sm text-stone-300">Open <strong>My learning</strong> for a Udemy-style grid of every course you’re enrolled in, with progress and continue links.</p>
        </div>
        <a href="{{ route('my-learning') }}" class="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-bold text-stone-900 shadow-sm transition hover:bg-stone-100">
            My learning
        </a>
    </section>

    <section class="mb-10 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500">Your account</h2>
        <p class="mt-1 text-xs text-stone-500">This page opens from <strong>Profile</strong> in the top navigation on any space page.</p>
        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-stone-500">Name</dt>
                <dd class="font-medium text-stone-900">{{ auth()->user()->name }}</dd>
            </div>
            <div>
                <dt class="text-stone-500">Email</dt>
                <dd class="font-medium text-stone-900 break-all">{{ auth()->user()->email }}</dd>
            </div>
            @if (auth()->user()->phone)
                <div>
                    <dt class="text-stone-500">Phone</dt>
                    <dd class="font-medium text-stone-900">{{ auth()->user()->phone }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-stone-500">Platform role</dt>
                <dd class="font-medium text-stone-900">
                    @if (auth()->user()->is_super_admin)
                        Super admin
                    @else
                        Standard user
                    @endif
                </dd>
            </div>
        </dl>
        <p class="mt-4 text-xs text-stone-500">Your <strong>space roles</strong> (coach vs learner) are listed under each space below. Register or log in to a space’s URL to get a membership there.</p>
    </section>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Your spaces</h1>
            <p class="mt-2 text-sm text-stone-600">Open a coach’s catalog, resume a lesson, or manage programs if you’re staff.</p>
        </div>
        <a href="{{ route('my-learning') }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">My learning</a>
    </div>

    @if ($tenants->isEmpty())
        <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950">
            <p>You’re not linked to any tenant yet. After seeding, try the demo learner account or ask a coach for access.</p>
            @if ($demoTenant)
                <a href="{{ route('learn.catalog', $demoTenant) }}" class="mt-4 inline-block rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Browse demo catalog ({{ $demoTenant->name }})
                </a>
            @endif
        </div>
    @else
        <ul class="mt-8 space-y-3">
            @foreach ($tenants as $tenant)
                @php
                    $m = $memberships->get($tenant->id);
                    $isStaff = $m && in_array($m->role, \App\Enums\TenantRole::staffValues(), true);
                @endphp
                <li class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-medium text-stone-900">{{ $tenant->name }}</h2>
                            <p class="text-xs text-stone-500">{{ $tenant->slug }} @if ($m) · Role: <span class="font-medium text-stone-700">{{ $m->role }}</span> @else · <span class="text-amber-700">No membership (enrolled only)</span> @endif</p>
                            <p class="mt-2 text-xs text-stone-500 break-all">
                                Share:
                                <a href="{{ $tenant->publicUrl('register') }}" class="font-medium text-teal-700 hover:underline">{{ $tenant->publicUrl('register') }}</a>
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-sm">
                            <a href="{{ route('learn.catalog', $tenant) }}" class="rounded-full border border-stone-300 px-3 py-1.5 hover:border-teal-400 hover:text-teal-800">Catalog</a>
                            <a href="{{ route('learn.continue', $tenant) }}" class="rounded-full bg-teal-600 px-3 py-1.5 text-white hover:bg-teal-700">Continue</a>
                            @if ($isStaff)
                                <a href="{{ route('coach.programs.index', $tenant) }}" class="rounded-full border border-teal-300 bg-teal-50 px-3 py-1.5 text-teal-900 hover:bg-teal-100">Coach</a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($demoTenant && $tenants->isNotEmpty())
        <p class="mt-8 text-xs text-stone-500">
            Demo tenant:
            <a href="{{ route('learn.catalog', $demoTenant) }}" class="text-teal-700 hover:underline">{{ $demoTenant->slug }}</a>
        </p>
    @endif
@endsection
