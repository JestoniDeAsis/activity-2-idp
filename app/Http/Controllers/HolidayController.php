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

        // Regular and Special days: fetched live from the external API (Nager.Date) on every request.
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

        // Special days from config/holidays_special.php (mode: nager | manual | calendarific).
        // null = use Nager's own special days; an array replaces them.
        $specialOverride = $this->specialOverride($year);

        $islamicMode = config('holidays_special.islamic_mode', 'manual'); // [ISLAMIC] manual | nager | calendarific

        $holidays = collect($rows)
            ->filter(fn ($h) => is_array($h) && isset($h['date'], $h['name']) && $this->isPublic($h))
            ->map(fn ($h) => [
                'date' => (string) $h['date'],
                'name' => (string) $h['name'],
                'local_name' => (string) ($h['localName'] ?? ''),
                'type' => $this->classify((string) $h['name'], (string) ($h['localName'] ?? '')),
                'tentative' => false,
                'source' => '',
            ])
            // [ISLAMIC] Only 'nager' mode keeps Nager's own Islamic days; the other modes supply their own.
            ->reject(fn ($h) => $h['type'] === 'islamic' && $islamicMode !== 'nager')
            // [SPECIAL] When manual/calendarific supplies this year, it replaces Nager's special days.
            ->reject(fn ($h) => $specialOverride !== null && $h['type'] === 'special')
            ->values()
            ->concat($this->islamicByMode($islamicMode, $year))
            ->concat($specialOverride ?? []); // [SPECIAL]

        return response()->json([
            'year' => $year,
            'holidays' => $holidays->sortBy('date')->values()->all(),
        ]);
    }

    // [SPECIAL] Chooses the special-day source. Returns null to keep Nager's own special days.
    private function specialOverride(int $year): ?array
    {
        return match (config('holidays_special.mode', 'manual')) {
            'manual' => $this->manualSpecialDays($year),
            'calendarific' => $this->calendarificSpecialDays($year),
            default => null, // 'nager'
        };
    }

    // [SPECIAL] Google/proclamation list in config/holidays_special.php. null if the year isn't listed.
    private function manualSpecialDays(int $year): ?array
    {
        $entry = config('holidays_special.years.' . $year);

        if (! is_array($entry) || empty($entry['days'])) {
            return null;
        }

        return collect($entry['days'])
            ->map(fn ($name, $monthDay) => [
                'date' => $year . '-' . $monthDay,
                'name' => (string) $name,
                'local_name' => '',
                'type' => 'special',
                'tentative' => false,
                'source' => (string) ($entry['source'] ?? ''),
            ])
            ->values()
            ->all();
    }

    // [ISLAMIC] manual = config/holidays.php dates, calendarific = Calendarific, nager = none added here.
    private function islamicByMode(string $mode, int $year): array
    {
        if ($mode === 'nager') {
            return [];
        }

        if ($mode === 'calendarific') {
            $rows = $this->calendarificRows($year);

            if ($rows !== null) {
                return collect($rows)
                    ->filter(fn ($h) => is_array($h) && isset($h['name']) && data_get($h, 'date.iso') && $this->isOfficialIslamic((string) $h['name']))
                    ->map(function ($h) {
                        $raw = (string) $h['name'];

                        return array_merge($this->calendarificEntry($h, 'islamic'), [
                            'name' => trim(preg_replace('/\s*\((?:provisional|tentative)[^)]*\)/i', '', $raw)),
                            'tentative' => (bool) preg_match('/provisional|tentative/i', $raw),
                        ]);
                    })
                    ->unique(fn ($h) => $h['date'] . '|' . $h['name'])
                    ->values()
                    ->all();
            }

            Log::warning('Calendarific unavailable for Islamic days; using manual dates.');
        }

        return $this->islamicHolidays($year);
    }

    // [SPECIAL] Calendarific: keep its national holidays that our own classify() calls "special"
    // (so Regular and Islamic ones are not duplicated). null on any failure -> falls back to Nager.
    private function calendarificSpecialDays(int $year): ?array
    {
        $rows = $this->calendarificRows($year);

        if ($rows === null) {
            return null;
        }

        $keep = array_map('strtolower', (array) config('holidays_special.calendarific.types', ['National holiday']));

        return collect($rows)
            ->filter(fn ($h) => is_array($h) && isset($h['name']) && data_get($h, 'date.iso'))
            ->filter(fn ($h) => collect((array) ($h['type'] ?? []))
                ->contains(fn ($t) => in_array(strtolower((string) $t), $keep, true)))
            ->filter(fn ($h) => $this->classify((string) $h['name'], '') === 'special')
            ->map(fn ($h) => $this->calendarificEntry($h, 'special'))
            ->unique('date')
            ->values()
            ->all();
    }

    private function calendarificEntry(array $h, string $type): array
    {
        return [
            'date' => substr((string) data_get($h, 'date.iso'), 0, 10),
            'name' => (string) $h['name'],
            'local_name' => '',
            'type' => $type,
            'tentative' => false,
            'source' => 'Calendarific',
        ];
    }

    // Calendarific's full holiday list for the year (cached). null if the key is missing or the call fails.
    private function calendarificRows(int $year): ?array
    {
        $key = config('holidays_special.calendarific.key');

        if (! $key) {
            Log::warning('Calendarific key missing (CALENDARIFIC_KEY).');

            return null;
        }

        try {
            return Cache::store('file')->remember("calendarific.ph.{$year}", (int) config('holidays_special.calendarific.cache_ttl', 86400), function () use ($year, $key) {
                $res = Http::timeout(10)->acceptJson()->get('https://calendarific.com/api/v2/holidays', [
                    'api_key' => $key,
                    'country' => config('holidays.country'),
                    'year' => $year,
                ]);

                $list = $res->successful() ? data_get($res->json(), 'response.holidays') : null;

                if (! is_array($list) || $list === []) {
                    throw new \RuntimeException('Calendarific returned no data (HTTP ' . $res->status() . ').');
                }

                return $list;
            });
        } catch (Throwable $e) {
            Log::warning('Calendarific failed: ' . $e->getMessage());

            return null;
        }
    }

    // Eid'l Fitr and Eid'l Adha: the officially proclaimed dates (see config/holidays.php).
    private function islamicHolidays(int $year): array
    {
        return collect(config('holidays.islamic_dates.' . $year, []))
            ->map(fn ($h) => [
                'date' => (string) $h['date'],
                'name' => (string) $h['name'],
                'local_name' => (string) ($h['local_name'] ?? ''),
                'type' => 'islamic',
                'tentative' => (bool) ($h['tentative'] ?? false),
                'source' => (string) ($h['source'] ?? ''),
            ])
            ->all();
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