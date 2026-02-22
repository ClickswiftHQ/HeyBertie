<?php

namespace App\Services;

use App\Support\PostcodeFormatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function __construct(private SearchService $searchService) {}

    /**
     * Geocode a postcode, city name, or address to lat/lng.
     *
     * 1. Postcode detected → local postcodes table → Ideal Postcodes API
     * 2. City/town name → SearchService hardcoded list
     * 3. Neither → null
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(string $address): ?array
    {
        $cacheKey = 'geocode:'.md5($address);

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($address) {
            $postcode = $this->extractPostcode($address);

            if ($postcode) {
                return $this->geocodePostcode($postcode);
            }

            // Try city/town name lookup
            $slug = str($address)->lower()->slug()->value();
            $location = $this->searchService->resolveLocation($slug);

            if ($location) {
                return [
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                ];
            }

            return null;
        });
    }

    /**
     * Look up all addresses at a given postcode.
     *
     * @return array<int, array{line_1: string, line_2: string, line_3: string, post_town: string, county: string, postcode: string, latitude: float, longitude: float}>|null
     */
    public function lookupPostcode(string $postcode): ?array
    {
        $normalised = PostcodeFormatter::format($postcode);
        $cacheKey = 'postcode_lookup:'.md5($normalised);

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($normalised) {
            $apiKey = config('services.ideal_postcodes.api_key');

            if (! $apiKey) {
                return null;
            }

            // API expects no-space format
            $apiPostcode = str_replace(' ', '', $normalised);

            $response = Http::get('https://api.ideal-postcodes.co.uk/v1/postcodes/'.urlencode($apiPostcode), [
                'api_key' => $apiKey,
            ]);

            if (! $response->successful() || $response->json('code') !== 2000) {
                return null;
            }

            return collect($response->json('result'))->map(fn (array $item) => [
                'line_1' => $item['line_1'] ?? '',
                'line_2' => $item['line_2'] ?? '',
                'line_3' => $item['line_3'] ?? '',
                'post_town' => $item['post_town'] ?? '',
                'county' => $item['county'] ?? '',
                'postcode' => $item['postcode'] ?? '',
                'latitude' => (float) $item['latitude'],
                'longitude' => (float) $item['longitude'],
            ])->all();
        });
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
     * Geocode a postcode: check local table first, then fall back to Ideal Postcodes API.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    private function geocodePostcode(string $postcode): ?array
    {
        $formatted = PostcodeFormatter::format($postcode);

        // Check local postcodes table first
        $local = DB::table('postcodes')
            ->where('postcode', $formatted)
            ->first();

        if ($local) {
            return [
                'latitude' => (float) $local->latitude,
                'longitude' => (float) $local->longitude,
            ];
        }

        // Fall back to Ideal Postcodes API
        $results = $this->lookupPostcode($postcode);

        if (empty($results)) {
            return null;
        }

        return [
            'latitude' => $results[0]['latitude'],
            'longitude' => $results[0]['longitude'],
        ];
    }
}
