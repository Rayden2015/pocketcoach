<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();

        return view('profile.show', [
            'user' => $user,
            'timezones' => $this->timezoneChoicesFor($user),
            'locales' => $this->localeChoices(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $input = $request->only([
            'name',
            'headline',
            'bio',
            'phone',
            'avatar_url',
            'linkedin_url',
            'website_url',
            'twitter_url',
            'timezone',
            'locale',
        ]);

        foreach (['headline', 'bio', 'phone', 'avatar_url', 'linkedin_url', 'website_url', 'twitter_url'] as $key) {
            if (! isset($input[$key]) || $input[$key] === '') {
                $input[$key] = null;

                continue;
            }
            if (is_string($input[$key])) {
                $input[$key] = trim($input[$key]);
                if ($input[$key] === '') {
                    $input[$key] = null;
                }
            }
        }

        foreach (['linkedin_url', 'website_url', 'twitter_url', 'avatar_url'] as $urlKey) {
            if (! empty($input[$urlKey]) && is_string($input[$urlKey]) && ! preg_match('#^https?://#i', $input[$urlKey])) {
                $input[$urlKey] = 'https://'.$input[$urlKey];
            }
        }

        $timezoneIds = DateTimeZone::listIdentifiers();
        $localeKeys = array_keys($this->localeChoices());
        $localeAllowed = array_values(array_unique(array_merge(
            $localeKeys,
            array_filter([(string) $request->user()->locale]),
        )));

        $validated = validator(
            $input,
            [
                'name' => ['required', 'string', 'max:255'],
                'headline' => ['nullable', 'string', 'max:255'],
                'bio' => ['nullable', 'string', 'max:5000'],
                'phone' => ['nullable', 'string', 'max:48'],
                'avatar_url' => ['nullable', 'url', 'max:2048'],
                'linkedin_url' => ['nullable', 'url', 'max:512'],
                'website_url' => ['nullable', 'url', 'max:512'],
                'twitter_url' => ['nullable', 'url', 'max:512'],
                'timezone' => ['nullable', 'string', 'max:64', Rule::in($timezoneIds)],
                'locale' => ['nullable', 'string', 'max:16', Rule::in($localeAllowed)],
            ],
        )->validate();

        $request->user()->fill(Arr::only($validated, [
            'name',
            'headline',
            'bio',
            'phone',
            'avatar_url',
            'linkedin_url',
            'website_url',
            'twitter_url',
            'timezone',
            'locale',
        ]));
        $request->user()->save();

        return redirect()->route('profile')->with('status', 'Profile updated.');
    }

    /**
     * @return list<string>
     */
    private function timezoneChoicesFor(?User $user): array
    {
        $preferred = [
            'Africa/Accra',
            'Africa/Lagos',
            'Africa/Nairobi',
            'Africa/Johannesburg',
            'Africa/Cairo',
            'Europe/London',
            'America/New_York',
            'America/Los_Angeles',
            'UTC',
        ];

        $list = collect($preferred)
            ->merge(DateTimeZone::listIdentifiers(DateTimeZone::AFRICA));

        if ($user?->timezone && in_array($user->timezone, DateTimeZone::listIdentifiers(), true)) {
            $list->push($user->timezone);
        }

        return $list->unique()->sort()->values()->all();
    }

    /**
     * @return array<string, string>
     */
    private function localeChoices(): array
    {
        return [
            'en' => 'English',
            'fr' => 'Français',
            'pt' => 'Português',
            'ar' => 'العربية',
            'tw' => 'Twi',
            'ha' => 'Hausa',
        ];
    }
}
