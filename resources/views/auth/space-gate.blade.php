@extends('layouts.app')

@section('title', 'Find your space')

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-stone-900">Sign in from your coach’s link</h1>
        <p class="mt-3 text-sm leading-relaxed text-stone-600">
            Pocket Coach spaces live at <strong class="text-stone-800">{{ parse_url(config('app.url'), PHP_URL_HOST) }}/<em>your-coach-slug</em></strong>.
            Open the URL your coach shared, then use <strong>Log in</strong> or <strong>Register</strong> on that page so your account is tied to the right space and branding.
        </p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:border-teal-400 hover:text-teal-800">Back to home</a>
            <a href="{{ route('create-space') }}" class="inline-flex justify-center rounded-full bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700">I’m a coach — create a space</a>
        </div>
        <p class="mt-8 text-xs text-stone-500">
            Demo: learner sign-in —
            <a href="{{ route('space.login', ['tenant' => 'adeola']) }}" class="font-medium text-teal-700 hover:underline"><code class="rounded bg-stone-100 px-1 py-0.5">{{ url('/adeola/login') }}</code></a>
        </p>
    </div>
@endsection
