<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HolidayController extends Controller
{
    private const API = 'https://date.nager.at/api/v3/PublicHolidays';

    public function show(int $year): JsonResponse
    {
        $min = (int) config('holidays.min_year');
        $max = (int) config('holidays.max_year');

        if ($year < $min || $year > $max) {
            return response()->json(['message' => "Choose a year from {$min} to {$max}."], 422);
        }

        // Regular and Special days: fetched live from Nager.Date on every request.
        try {
            $res = Http::timeout(10)->acceptJson()
                ->get(self::API . '/' . $year . '/' . config('holidays.country'));
        } catch (Throwable $e) {
            Log::warning('Holiday API failed: ' . $e->getMessage());

            return response()->json(['message' => 'The holiday service could not be reached. Please try again.'], 502);
        }

        $rows = $res->successful() ? $res->json() : null;

        if (! is_array($rows) || $rows === []) {
            return response()->json(['message' => "No holiday data was returned for {$year}."], 502);
        }

        $holidays = collect($rows)
            ->filter(fn ($h) => is_array($h) && isset($h['date'], $h['name']) && $this->isPublic($h))
            ->map(fn ($h) => [
                'date' => (string) $h['date'],
                'name' => (string) $h['name'],
                'local_name' => (string) ($h['localName'] ?? ''),
                'type' => $this->classify((string) $h['name'], (string) ($h['localName'] ?? '')),
                'tentative' => false,
            ])
            ->values();

        // Islamic holidays: Nager.Date has none for the Philippines, so ask Calendarific.
        // If it fails, the rest of the list still loads and the page shows a short message.
        $islamicError = null;

        if (! $holidays->contains('type', 'islamic')) {
            [$islamic, $islamicError] = $this->islamicHolidays($year);
            $holidays = $holidays->concat($islamic);
        }

        return response()->json([
            'year' => $year,
            'holidays' => $holidays->sortBy('date')->values()->all(),
            'islamic_error' => $islamicError,
        ]);
    }

    // Returns [list of holidays, error message or null].
    private function islamicHolidays(int $year): array
    {
        $key = (string) config('holidays.calendarific.key');

        if ($key === '') {
            Log::warning('CALENDARIFIC_API_KEY is empty, so Islamic holidays cannot be loaded.');

            return [[], 'Islamic holiday data is not available right now.'];
        }

        // Saved for 6 hours in a file (not the database) so testing does not use up the free 500 calls a month.
        $cache = Cache::store('file');
        $cacheKey = 'holidays:calendarific:' . config('holidays.country') . ':' . $year;
        $hit = $cache->get($cacheKey);

        if (is_array($hit) && $hit !== []) {
            return [$hit, null];
        }

        try {
            $res = Http::timeout(10)->acceptJson()->get(config('holidays.calendarific.url'), [
                'api_key' => $key,
                'country' => config('holidays.country'),
                'year' => $year,
            ]);
        } catch (Throwable $e) {
            // The error text can contain the URL with the key in it, so hide the key before logging.
            Log::warning('Calendarific request failed: ' . str_replace($key, '[hidden]', $e->getMessage()));

            return [[], 'Islamic holiday data could not be loaded right now. Please try again later.'];
        }

        $rows = $res->successful() ? $res->json('response.holidays') : null;

        if (! is_array($rows)) {
            Log::warning('Calendarific failed: HTTP ' . $res->status());

            return [[], 'Islamic holiday data could not be loaded right now. Please try again later.'];
        }

        $list = collect($rows)
            ->filter(fn ($h) => is_array($h) && isset($h['name'], $h['date']['iso']) && $this->isOfficialIslamic((string) $h['name']))
            ->map(function ($h) {
                $raw = (string) $h['name'];

                return [
                    'date' => substr((string) $h['date']['iso'], 0, 10),
                    'name' => trim(preg_replace('/\s*\((?:provisional|tentative)[^)]*\)/i', '', $raw)),
                    'local_name' => '',
                    'type' => 'islamic',
                    'tentative' => (bool) preg_match('/provisional|tentative/i', $raw),
                ];
            })
            ->unique(fn ($h) => $h['date'] . '|' . $h['name'])
            ->values()
            ->all();

        if ($list === []) {
            return [[], "No Islamic holiday data was returned for {$year}."];
        }

        $cache->put($cacheKey, $list, now()->addHours(6));

        return [$list, null];
    }

    // Only the two national Islamic holidays (Eid'l Fitr and Eid'l Adha), not "Day 2" or other observances.
    private function isOfficialIslamic(string $name): bool
    {
        $lower = Str::lower($name);

        if (preg_match('/day\s*2/', $lower)) {
            return false;
        }

        $letters = preg_replace('/[^a-z]/', '', $lower);

        return str_contains($letters, 'eid')
            && (str_contains($letters, 'fitr') || str_contains($letters, 'adha'));
    }

    // Keep only official public holidays (skips observances or optional days if the API lists any).
    private function isPublic(array $holiday): bool
    {
        $types = $holiday['types'] ?? ['Public'];

        return collect((array) $types)->contains(fn ($t) => strcasecmp((string) $t, 'Public') === 0);
    }

    private function classify(string $name, string $localName): string
    {
        $text = Str::lower($name . ' ' . $localName);
        $text = str_replace(["'", '’'], '', $text);

        foreach (config('holidays.islamic') as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'islamic';
            }
        }

        foreach (config('holidays.regular') as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'regular';
            }
        }

        return 'special';
    }
}