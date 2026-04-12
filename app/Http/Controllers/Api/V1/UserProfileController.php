<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function update(Request $request): JsonResponse
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
        $localeKeys = ['en', 'fr', 'pt', 'ar', 'tw', 'ha'];
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

        return response()->json([
            'data' => $request->user()->fresh(),
        ]);
    }
}
