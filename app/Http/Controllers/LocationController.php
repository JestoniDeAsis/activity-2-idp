<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

// Small proxy for the register page dropdowns. The browser only talks to our own site;
// Laravel calls the public APIs and caches good answers for a day.
// Every endpoint always answers 200 with a list. An empty list means "could not load",
// and register.js then lets the user type the value instead.
class LocationController extends Controller
{
    private const STATES_URL = 'https://countriesnow.space/api/v0.1/countries/states';
    private const CITIES_URL = 'https://countriesnow.space/api/v0.1/countries/state/cities';
    private const ZIP_URL = 'https://api.zippopotam.us';

    public function states(Request $request): JsonResponse
    {
        $country = (string) $request->query('country', '');

        if (! $this->countryConfig($country)) {
            return response()->json([]);
        }

        return response()->json($this->stateList($country));
    }

    public function cities(Request $request): JsonResponse
    {
        $country = (string) $request->query('country', '');
        $state = trim((string) $request->query('state', ''));

        if (! $this->countryConfig($country) || $state === '' || mb_strlen($state) > 100) {
            return response()->json([]);
        }

        $cities = $this->cached('loc:cities:' . md5($country . '|' . $state), function () use ($country, $state) {
            $res = Http::timeout(8)->acceptJson()->asJson()
                ->post(self::CITIES_URL, ['country' => $country, 'state' => $state]);

            if (! $res->successful()) {
                return [];
            }

            return collect($res->json('data', []))
                ->filter(fn ($c) => is_string($c))
                ->map(fn ($c) => trim($c))
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });

        return response()->json($cities);
    }

    public function zips(Request $request): JsonResponse
    {
        $country = (string) $request->query('country', '');
        $state = trim((string) $request->query('state', ''));
        $city = trim((string) $request->query('city', ''));
        $config = $this->countryConfig($country);

        if (! $config || ! $config['zip_lookup'] || $state === '' || $city === '' || mb_strlen($city) > 100) {
            return response()->json([]);
        }

        // Zippopotam wants the state/province code, which the states list carries.
        $code = collect($this->stateList($country))->firstWhere('name', $state)['code'] ?? '';

        if ($code === '') {
            return response()->json([]);
        }

        $zips = $this->cached('loc:zips:' . md5($country . '|' . $state . '|' . $city), function () use ($config, $code, $city) {
            $url = self::ZIP_URL . '/' . strtolower($config['iso'])
                . '/' . rawurlencode(strtolower($code))
                . '/' . rawurlencode($city);

            $res = Http::timeout(8)->acceptJson()->get($url);

            if (! $res->successful()) {
                return [];
            }

            return collect($res->json('places', []))
                ->pluck('post code')
                ->map(fn ($z) => trim((string) $z))
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL)
                ->values()
                ->all();
        });

        return response()->json($zips);
    }

    private function countryConfig(string $name): ?array
    {
        return config('location_api.countries')[$name] ?? null;
    }

    private function stateList(string $country): array
    {
        return $this->cached('loc:states:' . md5($country), function () use ($country) {
            $res = Http::timeout(8)->acceptJson()->asJson()
                ->post(self::STATES_URL, ['country' => $country]);

            if (! $res->successful()) {
                return [];
            }

            return collect($res->json('data.states', []))
                ->map(fn ($s) => [
                    'name' => trim((string) ($s['name'] ?? '')),
                    'code' => trim((string) ($s['state_code'] ?? '')),
                ])
                ->filter(fn ($s) => $s['name'] !== '')
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }

    // Only good (non-empty) answers are cached, so a failed call is retried next time.
    private function cached(string $key, callable $fetch): array
    {
        $hit = Cache::get($key);

        if (is_array($hit) && $hit !== []) {
            return $hit;
        }

        try {
            $list = $fetch();
        } catch (Throwable $e) {
            Log::warning('Location lookup failed: ' . $e->getMessage());

            return [];
        }

        if ($list !== []) {
            Cache::put($key, $list, now()->addDay());
        }

        return $list;
    }
}