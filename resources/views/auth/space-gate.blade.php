@extends('layouts.app')

@section('title', 'Open a space from its link')

@section('content')
    <div class="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-stone-900">Joining from a link?</h1>
        <p class="mt-3 text-sm leading-relaxed text-stone-600">
            Each space has its own address: <strong class="text-stone-800">{{ parse_url(config('app.url'), PHP_URL_HOST) }}/<em>space-name</em></strong>.
            Open that link and use <strong>Log in</strong> or <strong>Register</strong> there so you land in the right place with one click after signing in.
        </p>
        <p class="mt-3 text-sm text-stone-600">
            You can also <a href="{{ route('login') }}" class="font-medium text-teal-700 hover:underline">log in from the main site</a> — same password everywhere — then pick a space from your home.
        </p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-800 hover:border-teal-400 hover:text-teal-800">Browse all spaces</a>
            <a href="{{ route('login') }}" class="inline-flex justify-center rounded-full bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Main log in</a>
            <a href="{{ route('create-space') }}" class="inline-flex justify-center rounded-full border border-teal-200 bg-teal-50 px-5 py-2.5 text-sm font-medium text-teal-900 hover:bg-teal-100">Create a coaching space</a>
        </div>
        <p class="mt-8 text-xs text-stone-500">
            Example URL:
            <a href="{{ route('space.login', ['tenant' => 'adeola']) }}" class="font-medium text-teal-700 hover:underline"><code class="rounded bg-stone-100 px-1 py-0.5">{{ url('/adeola/login') }}</code></a>
        </p>
    </div>
@endsection
