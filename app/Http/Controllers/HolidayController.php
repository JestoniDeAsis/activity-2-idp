<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
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

        // Fetched live on every request. Nothing is saved in the database.
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
            ])
            ->sortBy('date')
            ->values()
            ->all();

        return response()->json(['year' => $year, 'holidays' => $holidays]);
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