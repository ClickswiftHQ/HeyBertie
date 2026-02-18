<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(string $address): ?array
    {
        $cacheKey = 'geocode:' . md5($address);

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($address) {
            $apiKey = config('services.google.maps_api_key');

            if (! $apiKey) {
                return null;
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
                'region' => 'gb',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $results = $response->json('results');

            if (empty($results)) {
                return null;
            }

            $location = $results[0]['geometry']['location'];

            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
            ];
        });
    }

    /**
     * @return array{address: string, city: string, postcode: string}|null
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $cacheKey = 'reverse_geocode:' . md5("{$lat},{$lng}");

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($lat, $lng) {
            $apiKey = config('services.google.maps_api_key');

            if (! $apiKey) {
                return null;
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $results = $response->json('results');

            if (empty($results)) {
                return null;
            }

            $components = collect($results[0]['address_components']);

            return [
                'address' => $results[0]['formatted_address'],
                'city' => $components->firstWhere(fn ($c) => in_array('postal_town', $c['types']))['long_name'] ?? '',
                'postcode' => $components->firstWhere(fn ($c) => in_array('postal_code', $c['types']))['long_name'] ?? '',
            ];
        });
    }
}
