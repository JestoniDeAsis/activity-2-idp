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
// The Philippines uses PSGC (the official Philippine geographic codes); other countries use
// CountriesNow (states, cities) and Zippopotam (ZIP codes).
class LocationController extends Controller
{
    private const STATES_URL = 'https://countriesnow.space/api/v0.1/countries/states';
    private const CITIES_URL = 'https://countriesnow.space/api/v0.1/countries/state/cities';
    private const ZIP_URL = 'https://api.zippopotam.us';
    private const PSGC_URL = 'https://psgc.gitlab.io/api';

    // Metro Manila (NCR): the 16 cities and 1 municipality from the PSA list. PSGC files them
    // under districts instead of a province, so they are fixed here to never come back empty.
    private const NCR_CITIES = [
        'City of Caloocan',
        'City of Las Piñas',
        'City of Makati',
        'City of Malabon',
        'City of Mandaluyong',
        'City of Manila',
        'City of Marikina',
        'City of Muntinlupa',
        'City of Navotas',
        'City of Parañaque',
        'City of Pasay',
        'City of Pasig',
        'City of San Juan',
        'City of Taguig',
        'City of Valenzuela',
        'Pateros',
        'Quezon City',
    ];

    public function states(Request $request): JsonResponse
    {
        $country = (string) $request->query('country', '');

        if (! $this->countryConfig($country)) {
            return response()->json([]);
        }

        if ($country === 'Philippines') {
            return response()->json($this->phStates());
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

        if ($country === 'Philippines') {
            return response()->json($this->phCities($state));
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

        if (! $config || $state === '' || $city === '' || mb_strlen($city) > 100) {
            return response()->json([]);
        }

        // The Philippines: Metro Manila has a fixed ZIP list per city (config/ncr_zips.php).
        // Other provinces use a suggestion list (resources/data/ph_zips.json).
        if ($country === 'Philippines') {
            return response()->json($this->phZips($state, $city));
        }

        if (! $config['zip_lookup']) {
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

    // ---------- Philippines (PSGC) ----------

    // Provinces, plus Metro Manila (NCR). PSGC has no provinces for Metro Manila (it uses districts),
    // so it is added as its own entry and its cities are fixed in NCR_CITIES.
    private function phStates(): array
    {
        return $this->cached('loc:ph:states:v2', function () {
            $provinces = $this->psgc('/provinces.json');

            if ($provinces === null) {
                return [];
            }

            return collect($provinces)
                ->filter(fn ($p) => is_array($p))
                ->map(fn ($p) => [
                    'name' => trim((string) ($p['name'] ?? '')),
                    'code' => trim((string) ($p['code'] ?? '')),
                ])
                ->filter(fn ($s) => $s['name'] !== '' && $s['code'] !== '')
                ->push(['name' => 'Metro Manila (NCR)', 'code' => 'ncr'])
                ->unique('name')
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }

    // All cities and municipalities of the chosen province (or of Metro Manila).
    private function phCities(string $stateName): array
    {
        $state = collect($this->phStates())->firstWhere('name', $stateName);

        if (! $state) {
            return [];
        }

        if ($state['code'] === 'ncr') {
            return self::NCR_CITIES;
        }

        $code = $state['code'];
        $path = '/provinces/' . $code . '/cities-municipalities.json';

        return $this->cached('loc:ph:cities:' . md5($code), function () use ($path) {
            $rows = $this->psgc($path);

            if ($rows === null) {
                return [];
            }

            return collect($rows)
                ->filter(fn ($c) => is_array($c))
                ->map(fn ($c) => trim((string) ($c['name'] ?? '')))
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }

    // ZIP codes for the chosen city, each as ['code' => ..., 'area' => ...] (area can be empty).
    // Metro Manila (NCR): fixed list in config/ncr_zips.php, and the server enforces it.
    // Other provinces: resources/data/ph_zips.json, which is only a suggestion list.
    // Both are local files, so there is no API call and nothing to fail.
    private function phZips(string $stateName, string $city): array
    {
        $rows = $stateName === 'Metro Manila (NCR)'
            ? (config('ncr_zips')[$city] ?? [])
            : $this->phProvinceZips($stateName, $city);

        $list = [];

        foreach ($rows as $code => $area) {
            $list[] = ['code' => (string) $code, 'area' => (string) $area];
        }

        return $list;
    }

    // Finds province > city in ph_zips.json with a loose name match (see placeKey).
    // No match gives an empty list, and then the user types the ZIP.
    private function phProvinceZips(string $stateName, string $city): array
    {
        $file = resource_path('data/ph_zips.json');

        if (! is_file($file)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            return [];
        }

        $stateKey = $this->placeKey($stateName);
        $cityKey = $this->placeKey($city);

        foreach ($data as $province => $cities) {
            if (! is_array($cities) || $this->placeKey((string) $province) !== $stateKey) {
                continue;
            }

            foreach ($cities as $name => $zips) {
                if (is_array($zips) && $this->placeKey((string) $name) === $cityKey) {
                    return $zips;
                }
            }
        }

        return [];
    }

    // PSGC and the postal list can spell a place a little differently
    // ("City of Cebu" / "Cebu City", "Sta." / "Santa"), so names are compared in a simple form.
    private function placeKey(string $name): string
    {
        $s = mb_strtolower($name);
        $s = strtr($s, ['ñ' => 'n', 'á' => 'a', 'à' => 'a', 'é' => 'e', 'è' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
        $s = preg_replace('/\(.*?\)/u', ' ', $s);
        $s = preg_replace('/\bsta\b\.?/u', 'santa', $s);
        $s = preg_replace('/\bsto\b\.?/u', 'santo', $s);
        $s = preg_replace('/\b(province of|city of|municipality of|city)\b/u', ' ', $s);

        return (string) preg_replace('/[^a-z0-9]+/', '', $s);
    }

    private function psgc(string $path): ?array
    {
        try {
            $res = Http::timeout(10)->acceptJson()->get(self::PSGC_URL . $path);
        } catch (Throwable $e) {
            Log::warning('PSGC request failed: ' . $e->getMessage());

            return null;
        }

        if (! $res->successful()) {
            Log::warning('PSGC failed: HTTP ' . $res->status() . ' for ' . $path);

            return null;
        }

        $data = $res->json();

        return is_array($data) ? $data : null;
    }

    // ---------- Other countries (CountriesNow) ----------

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