@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-teal-700">Account</p>
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Profile</h1>
            <p class="mt-1 text-sm text-stone-600">How you appear across {{ config('app.name') }}. Your email stays your login — it isn’t shown to other learners unless a coach shares rosters separately.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-800 hover:border-teal-400">Your spaces</a>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500">Basic info</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-stone-700">Display name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="headline" class="block text-sm font-medium text-stone-700">Headline</label>
                    <input type="text" id="headline" name="headline" value="{{ old('headline', $user->headline) }}" placeholder="e.g. Leadership coach · ACC"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    <p class="mt-1 text-xs text-stone-500">Short line under your name — role, credentials, niche.</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="bio" class="block text-sm font-medium text-stone-700">Bio</label>
                    <textarea id="bio" name="bio" rows="5" placeholder="Tell learners and coaches a bit about you."
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('bio', $user->bio) }}</textarea>
                </div>
                <div>
                    <label for="email_readonly" class="block text-sm font-medium text-stone-700">Email (login)</label>
                    <input type="email" id="email_readonly" value="{{ $user->email }}" readonly
                        class="mt-1 w-full cursor-not-allowed rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-stone-600">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-stone-700">Mobile / WhatsApp</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+233 …"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500">Links &amp; photo</h2>
            <p class="mt-1 text-sm text-stone-600">URLs must be valid; you can paste without <code class="rounded bg-stone-100 px-1 text-xs">https://</code> — we’ll add it when saving.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="avatar_url" class="block text-sm font-medium text-stone-700">Profile photo URL</label>
                    <input type="text" id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $user->avatar_url) }}" placeholder="https://…"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
                <div>
                    <label for="linkedin_url" class="block text-sm font-medium text-stone-700">LinkedIn</label>
                    <input type="text" id="linkedin_url" name="linkedin_url" value="{{ old('linkedin_url', $user->linkedin_url) }}" placeholder="linkedin.com/in/…"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
                <div>
                    <label for="website_url" class="block text-sm font-medium text-stone-700">Website</label>
                    <input type="text" id="website_url" name="website_url" value="{{ old('website_url', $user->website_url) }}" placeholder="yourdomain.com"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="twitter_url" class="block text-sm font-medium text-stone-700">X (Twitter) or other social URL</label>
                    <input type="text" id="twitter_url" name="twitter_url" value="{{ old('twitter_url', $user->twitter_url) }}" placeholder="https://x.com/…"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500">Preferences</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="timezone" class="block text-sm font-medium text-stone-700">Timezone</label>
                    <select id="timezone" name="timezone"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $user->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="locale" class="block text-sm font-medium text-stone-700">Preferred language</label>
                    <select id="locale" name="locale"
                        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @foreach ($locales as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <span class="block text-sm font-medium text-stone-700">Platform</span>
                    <p class="mt-1 text-sm text-stone-800">{{ $user->is_super_admin ? 'Super admin' : 'Member' }}</p>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-full bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                Save profile
            </button>
            <a href="{{ route('my-learning') }}" class="inline-flex items-center rounded-full border border-stone-300 px-6 py-2.5 text-sm font-medium text-stone-800 hover:border-teal-400">
                Cancel
            </a>
        </div>
    </form>
@endsection
