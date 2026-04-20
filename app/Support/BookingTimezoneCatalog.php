<?php

namespace App\Support;

/**
 * Curated IANA timezones for coach booking, labeled with GMT offset and example cities.
 * Stored values are IANA identifiers (e.g. Europe/Paris); labels are fixed hints (DST varies by zone).
 */
final class BookingTimezoneCatalog
{
    /**
     * @var list<array{tz: string, line: string}>
     */
    private const OPTIONS = [
        ['tz' => '', 'line' => '— Use my profile timezone, or app default —'],
        ['tz' => 'UTC', 'line' => 'GMT / UTC±0 — Accra, Dakar, Reykjavik, Zulu'],
        ['tz' => 'Atlantic/Reykjavik', 'line' => 'GMT — Iceland (no DST)'],
        ['tz' => 'Europe/London', 'line' => 'GMT / GMT+1 (UK) — London, Dublin, Lisbon'],
        ['tz' => 'Africa/Casablanca', 'line' => 'GMT+1 — Casablanca, Rabat'],
        ['tz' => 'Africa/Lagos', 'line' => 'GMT+1 — Lagos, West Central Africa'],
        ['tz' => 'Europe/Paris', 'line' => 'GMT+1 / GMT+2 — Paris, Brussels, Madrid, Berlin'],
        ['tz' => 'Africa/Johannesburg', 'line' => 'GMT+2 — Johannesburg, Harare, Windhoek'],
        ['tz' => 'Africa/Cairo', 'line' => 'GMT+2 — Cairo, Tripoli'],
        ['tz' => 'Europe/Athens', 'line' => 'GMT+2 / GMT+3 — Athens, Bucharest, Helsinki'],
        ['tz' => 'Africa/Nairobi', 'line' => 'GMT+3 — Nairobi, Mogadishu'],
        ['tz' => 'Europe/Istanbul', 'line' => 'GMT+3 — Istanbul, Kyiv, Riyadh'],
        ['tz' => 'Asia/Dubai', 'line' => 'GMT+4 — Dubai, Muscat, Baku'],
        ['tz' => 'Asia/Karachi', 'line' => 'GMT+5 — Karachi, Tashkent'],
        ['tz' => 'Asia/Kolkata', 'line' => 'GMT+5:30 — Mumbai, Delhi, Colombo'],
        ['tz' => 'Asia/Dhaka', 'line' => 'GMT+6 — Dhaka, Almaty'],
        ['tz' => 'Asia/Bangkok', 'line' => 'GMT+7 — Bangkok, Jakarta, Ho Chi Minh City'],
        ['tz' => 'Asia/Singapore', 'line' => 'GMT+8 — Singapore, Kuala Lumpur, Manila, Perth'],
        ['tz' => 'Asia/Shanghai', 'line' => 'GMT+8 — Shanghai, Hong Kong, Taipei'],
        ['tz' => 'Asia/Tokyo', 'line' => 'GMT+9 — Tokyo, Seoul, Pyongyang'],
        ['tz' => 'Australia/Sydney', 'line' => 'GMT+10 / GMT+11 — Sydney, Melbourne (DST)'],
        ['tz' => 'Pacific/Auckland', 'line' => 'GMT+12 / GMT+13 — Auckland, Fiji'],
        ['tz' => 'Pacific/Honolulu', 'line' => 'GMT−10 — Honolulu'],
        ['tz' => 'America/Los_Angeles', 'line' => 'GMT−8 / GMT−7 — Los Angeles, Vancouver'],
        ['tz' => 'America/Denver', 'line' => 'GMT−7 / GMT−6 — Denver, Calgary'],
        ['tz' => 'America/Chicago', 'line' => 'GMT−6 / GMT−5 — Chicago, Mexico City'],
        ['tz' => 'America/New_York', 'line' => 'GMT−5 / GMT−4 — New York, Toronto, Miami'],
        ['tz' => 'America/Halifax', 'line' => 'GMT−4 / GMT−3 — Halifax, Atlantic Canada'],
        ['tz' => 'America/Sao_Paulo', 'line' => 'GMT−3 — São Paulo, Buenos Aires'],
        ['tz' => 'America/Santiago', 'line' => 'GMT−4 / GMT−3 — Santiago'],
    ];

    /**
     * @return list<string>
     */
    public static function allowedIdentifiers(): array
    {
        $ids = [];
        foreach (self::OPTIONS as $row) {
            if ($row['tz'] !== '') {
                $ids[] = $row['tz'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function selectOptions(): array
    {
        $out = [];
        foreach (self::OPTIONS as $row) {
            $out[] = [
                'id' => $row['tz'],
                'label' => $row['line'],
            ];
        }

        return $out;
    }

    public static function isAllowed(?string $identifier): bool
    {
        if ($identifier === null || $identifier === '') {
            return true;
        }

        return in_array($identifier, self::allowedIdentifiers(), true);
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function selectOptionsIncluding(?string $savedTimezone): array
    {
        $opts = self::selectOptions();
        if ($savedTimezone !== null && $savedTimezone !== '' && ! in_array($savedTimezone, self::allowedIdentifiers(), true)) {
            array_splice($opts, 1, 0, [[
                'id' => $savedTimezone,
                'label' => $savedTimezone.' (current — choose a listed zone when you can)',
            ]]);
        }

        return $opts;
    }
}
