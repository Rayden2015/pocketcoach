@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-[var(--pc-accent)]">Account</p>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Profile</h1>
            <p class="mt-1 text-sm text-slate-600">How you appear in {{ config('app.name') }}.</p>
        </div>
        @if(auth()->user()->coachesAnySpace())
            <a href="{{ route('my-coaching') }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 shadow-sm hover:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)] hover:text-[var(--pc-brand)]">My coaching</a>
        @endif
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-[var(--pc-shadow)] backdrop-blur-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Basic info</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">Display name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                </div>
                <div class="sm:col-span-2">
                    <label for="headline" class="block text-sm font-medium text-slate-700">Headline</label>
                    <input type="text" id="headline" name="headline" value="{{ old('headline', $user->headline) }}" placeholder="e.g. Leadership coach · ACC"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                    <p class="mt-1 text-xs text-slate-500">Short line under your name — role, credentials, niche.</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="bio" class="block text-sm font-medium text-slate-700">Bio</label>
                    <textarea id="bio" name="bio" rows="5" placeholder="Tell learners and coaches a bit about you."
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">{{ old('bio', $user->bio) }}</textarea>
                </div>
                <div>
                    <label for="email_readonly" class="block text-sm font-medium text-slate-700">Email (login)</label>
                    <input type="email" id="email_readonly" value="{{ $user->email }}" readonly
                        class="mt-1 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Mobile / WhatsApp</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+233 …"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-[var(--pc-shadow)] backdrop-blur-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Links &amp; photo</h2>
            <p class="mt-1 text-sm text-slate-600">URLs must be valid; you can paste without <code class="rounded bg-slate-100 px-1 text-xs">https://</code> — we’ll add it when saving.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="avatar_url" class="block text-sm font-medium text-slate-700">Profile photo URL</label>
                    <input type="text" id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $user->avatar_url) }}" placeholder="https://…"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                </div>
                <div>
                    <label for="linkedin_url" class="block text-sm font-medium text-slate-700">LinkedIn</label>
                    <input type="text" id="linkedin_url" name="linkedin_url" value="{{ old('linkedin_url', $user->linkedin_url) }}" placeholder="linkedin.com/in/…"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                </div>
                <div>
                    <label for="website_url" class="block text-sm font-medium text-slate-700">Website</label>
                    <input type="text" id="website_url" name="website_url" value="{{ old('website_url', $user->website_url) }}" placeholder="yourdomain.com"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                </div>
                <div class="sm:col-span-2">
                    <label for="twitter_url" class="block text-sm font-medium text-slate-700">X (Twitter) or other social URL</label>
                    <input type="text" id="twitter_url" name="twitter_url" value="{{ old('twitter_url', $user->twitter_url) }}" placeholder="https://x.com/…"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-[var(--pc-shadow)] backdrop-blur-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Preferences</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="timezone" class="block text-sm font-medium text-slate-700">Timezone</label>
                    <select id="timezone" name="timezone"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $user->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="locale" class="block text-sm font-medium text-slate-700">Preferred language</label>
                    <select id="locale" name="locale"
                        class="pc-ring-focus mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)]">
                        @foreach ($locales as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <span class="block text-sm font-medium text-slate-700">Platform</span>
                    <p class="mt-1 text-sm text-slate-800">{{ $user->is_super_admin ? 'Super admin' : 'Member' }}</p>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="pc-ring-focus pc-btn-primary rounded-full px-6 py-2.5 text-sm font-semibold shadow-sm focus:outline-none">
                Save profile
            </button>
            <a href="{{ route('my-learning') }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-6 py-2.5 text-sm font-medium text-slate-800 shadow-sm hover:border-[color-mix(in_srgb,var(--pc-accent)_45%,#cbd5e1)] hover:text-[var(--pc-brand)]">
                Cancel
            </a>
        </div>
    </form>
@endsection
