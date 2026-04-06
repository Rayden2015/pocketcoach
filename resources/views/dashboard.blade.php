@extends('layouts.app')

@section('title', 'Your spaces')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">Your spaces</h1>
    <p class="mt-2 text-sm text-stone-600">Open a coach’s catalog, resume a lesson, or manage programs if you’re staff.</p>

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
                            <p class="text-xs text-stone-500">{{ $tenant->slug }} @if ($m) · {{ $m->role }} @endif</p>
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
