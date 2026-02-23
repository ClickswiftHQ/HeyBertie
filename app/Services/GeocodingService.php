<?php

namespace App\Services;

use App\Models\AddressCache;
use App\Models\GeocodeCache;
use App\Models\UnmatchedSearch;
use App\Support\PostcodeFormatter;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocode a postcode, city name, or address to lat/lng.
     *
     * 1. Check geocode_cache by slug
     * 2. Postcode detected → address_cache → Ideal Postcodes API → sector fallback
     * 3. Not found → log to unmatched_searches → null
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(string $address): ?array
    {
        $slug = str($address)->slug()->value();

        // Check geocode_cache by slug
        $cached = GeocodeCache::where('slug', $slug)->first();

        if ($cached) {
            return [
                'latitude' => $cached->latitude,
                'longitude' => $cached->longitude,
            ];
        }

        // Try postcode extraction
        $postcode = $this->extractPostcode($address);

        if ($postcode) {
            return $this->geocodePostcode($postcode);
        }

        // Not found — log as unmatched search
        $normalised = preg_replace('/\s+/', ' ', mb_strtolower(trim($address)));
        $unmatched = UnmatchedSearch::firstOrCreate(
            ['query' => $normalised],
            ['search_count' => 0],
        );
        $unmatched->increment('search_count');

        return null;
    }

    /**
     * Look up all addresses at a given postcode.
     *
     * Checks address_cache first, falls back to Ideal Postcodes API and caches results.
     *
     * @return array<int, array{line_1: string, line_2: string, line_3: string, post_town: string, county: string, postcode: string, latitude: float, longitude: float}>|null
     */
    public function lookupPostcode(string $postcode): ?array
    {
        $normalised = PostcodeFormatter::format($postcode);

        // Check address_cache
        $cached = AddressCache::where('postcode', $normalised)->get();

        if ($cached->isNotEmpty()) {
            return $cached->map(fn (AddressCache $row) => [
                'line_1' => $row->line_1,
                'line_2' => $row->line_2,
                'line_3' => $row->line_3,
                'post_town' => $row->post_town,
                'county' => $row->county,
                'postcode' => $row->postcode,
                'latitude' => $row->latitude,
                'longitude' => $row->longitude,
            ])->all();
        }

        // Call Ideal Postcodes API
        $apiKey = config('services.ideal_postcodes.api_key');

        if (! $apiKey) {
            return null;
        }

        $apiPostcode = str_replace(' ', '', $normalised);

        $response = Http::get('https://api.ideal-postcodes.co.uk/v1/postcodes/'.urlencode($apiPostcode), [
            'api_key' => $apiKey,
        ]);

        if (! $response->successful() || $response->json('code') !== 2000) {
            return null;
        }

        $addresses = collect($response->json('result'))->map(fn (array $item) => [
            'line_1' => $item['line_1'] ?? '',
            'line_2' => $item['line_2'] ?? '',
            'line_3' => $item['line_3'] ?? '',
            'post_town' => $item['post_town'] ?? '',
            'county' => $item['county'] ?? '',
            'postcode' => $item['postcode'] ?? '',
            'latitude' => (float) $item['latitude'],
            'longitude' => (float) $item['longitude'],
        ])->all();

        // Cache each address in address_cache
        $rows = array_map(fn (array $a) => [
            'postcode' => PostcodeFormatter::format($a['postcode']),
            'line_1' => $a['line_1'],
            'line_2' => $a['line_2'],
            'line_3' => $a['line_3'],
            'post_town' => $a['post_town'],
            'county' => $a['county'],
            'latitude' => $a['latitude'],
            'longitude' => $a['longitude'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $addresses);

        AddressCache::upsert($rows, ['postcode', 'line_1'], ['line_2', 'line_3', 'post_town', 'county', 'latitude', 'longitude', 'updated_at']);

        return $addresses;
    }

    /**
     * Suggest locations matching a partial input for autocomplete.
     *
     * Detects postcode-like input and matches by postcode_sector.
     * Weights results by settlement type: City > Town > Suburban Area > Village.
     *
     * @return array<int, array{slug: string, name: string, latitude: float, longitude: float, county: string, postcode_sector: string}>
     */
    public function suggest(string $input): array
    {
        $normalised = preg_replace('/\s+/', ' ', mb_strtolower(trim($input)));

        if ($normalised === '') {
            return [];
        }

        $isPostcodeLike = (bool) preg_match('/^[a-z]{1,2}\d/i', $normalised);
        $grammar = GeocodeCache::query()->getQuery()->getGrammar();

        $typeOrder = sprintf(
            'CASE %s WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END',
            $grammar->wrap('settlement_type'),
        );
        $typeBindings = ['City', 'Town', 'Suburban Area'];

        if ($isPostcodeLike) {
            // Postcode prefix — match by sector
            return GeocodeCache::query()
                ->where('postcode_sector', 'like', $normalised.'%')
                ->orderByRaw($typeOrder, $typeBindings)
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (GeocodeCache $row) => [
                    'slug' => $row->slug,
                    'name' => $row->display_name,
                    'latitude' => $row->latitude,
                    'longitude' => $row->longitude,
                    'county' => $row->county,
                    'postcode_sector' => $row->postcode_sector,
                ])
                ->all();
        }

        // Name-based search — split input into words so "London ful" matches "Fulham, London"
        $words = preg_split('/[\s,]+/', $normalised, -1, PREG_SPLIT_NO_EMPTY);
        $slugPrefix = str($normalised)->slug()->value();
        $slugCol = $grammar->wrap('slug');
        $nameCol = $grammar->wrap('name');
        $displayCol = $grammar->wrap('display_name');
        $countyCol = $grammar->wrap('county');

        $query = GeocodeCache::query();

        if (count($words) > 1) {
            // Multi-word: require every word to appear in name, display_name, or county
            foreach ($words as $word) {
                $pattern = '%'.$word.'%';
                $query->where(function ($q) use ($nameCol, $displayCol, $countyCol, $pattern) {
                    $q->whereRaw('LOWER('.$nameCol.') LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER('.$displayCol.') LIKE ?', [$pattern])
                        ->orWhereRaw('LOWER('.$countyCol.') LIKE ?', [$pattern]);
                });
            }
        } else {
            // Single word: match slug prefix or name substring (original behaviour)
            $query->where('slug', 'like', $slugPrefix.'%')
                ->orWhereRaw('LOWER('.$nameCol.') LIKE ?', ['%'.$normalised.'%']);
        }

        $nameOrder = sprintf(
            'CASE WHEN %1$s = ? THEN 0 WHEN %1$s LIKE ? THEN 1 ELSE 2 END, %2$s',
            $slugCol,
            $typeOrder,
        );

        return $query
            ->orderByRaw($nameOrder, [$slugPrefix, $slugPrefix.'%', ...$typeBindings])
            ->limit(8)
            ->get()
            ->map(fn (GeocodeCache $row) => [
                'slug' => $row->slug,
                'name' => $row->display_name,
                'latitude' => $row->latitude,
                'longitude' => $row->longitude,
                'county' => $row->county,
                'postcode_sector' => $row->postcode_sector,
            ])
            ->all();
    }

    /**
     * Extract a UK postcode from a string if present.
     */
    private function extractPostcode(string $input): ?string
    {
        $cleaned = strtoupper(trim($input));

        if (preg_match('/\b([A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2})\b/', $cleaned, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract the postcode sector (outward code + first digit of inward).
     *
     * e.g. "GU7 2AB" → "GU7 2", "SW1A 1AA" → "SW1A 1"
     */
    private function extractSector(string $postcode): ?string
    {
        $formatted = PostcodeFormatter::format($postcode);

        if (preg_match('/^([A-Z]{1,2}\d[A-Z\d]?\s*\d)/', $formatted, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Geocode a postcode: check address_cache → API → sector fallback.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    private function geocodePostcode(string $postcode): ?array
    {
        $formatted = PostcodeFormatter::format($postcode);

        // Check address_cache for this postcode
        $cached = AddressCache::where('postcode', $formatted)->first();

        if ($cached) {
            return [
                'latitude' => $cached->latitude,
                'longitude' => $cached->longitude,
            ];
        }

        // Fall back to API
        $results = $this->lookupPostcode($postcode);

        if (! empty($results)) {
            return [
                'latitude' => $results[0]['latitude'],
                'longitude' => $results[0]['longitude'],
            ];
        }

        // Fall back to postcode sector match
        $sector = $this->extractSector($postcode);

        if ($sector) {
            $sectorNormalised = mb_strtolower($sector);
            $sectorMatch = GeocodeCache::where('postcode_sector', 'like', $sectorNormalised.'%')->first();

            if ($sectorMatch) {
                return [
                    'latitude' => $sectorMatch->latitude,
                    'longitude' => $sectorMatch->longitude,
                ];
            }
        }

        return null;
    }
}
