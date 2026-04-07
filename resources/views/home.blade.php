@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Coaching, structured.</h1>
        <p class="mt-3 leading-relaxed text-stone-600">
            Pocket Coach is your multi-tenant LMS: programs, lessons, progress, and Paystack for Ghana &amp; Nigeria.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('public.catalog', ['tenant' => 'adeola']) }}" class="rounded-full border border-teal-200 bg-teal-50 px-5 py-2.5 text-sm font-medium text-teal-900 hover:bg-teal-100">Browse demo catalog</a>
            <a href="{{ route('create-space') }}" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Create a coaching space</a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:border-teal-400 hover:text-teal-800">Your spaces</a>
            @else
                <a href="{{ route('login') }}" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:border-teal-400 hover:text-teal-800">How do I sign in?</a>
            @endauth
        </div>
        <p class="mt-6 text-sm text-stone-500">Learners: open your coach’s link (<code class="rounded bg-stone-100 px-1">…/their-slug/register</code>) — not the generic “Create a space” flow.</p>
    </div>
@endsection
