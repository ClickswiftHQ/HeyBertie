<?php

use App\Models\GeocodeCache;
use App\Services\SearchService;

test('resolveLocation returns coordinates from geocode_cache', function () {
    GeocodeCache::create([
        'slug' => 'london',
        'name' => 'London',
        'display_name' => 'London',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $service = new SearchService;
    $result = $service->resolveLocation('london');

    expect($result)->not->toBeNull();
    expect($result['name'])->toBe('London');
    expect($result['latitude'])->toBe(51.5074);
    expect($result['longitude'])->toBe(-0.1278);
});

test('resolveLocation returns coordinates for known town', function () {
    GeocodeCache::create([
        'slug' => 'fulham-london',
        'name' => 'Fulham',
        'display_name' => 'Fulham, London',
        'latitude' => 51.4749,
        'longitude' => -0.2010,
    ]);

    $service = new SearchService;
    $result = $service->resolveLocation('fulham-london');

    expect($result)->not->toBeNull();
    expect($result['name'])->toBe('Fulham, London');
    expect($result['latitude'])->toBe(51.4749);
});

test('resolveLocation returns null for unknown location', function () {
    $service = new SearchService;
    $result = $service->resolveLocation('nonexistent-place');

    expect($result)->toBeNull();
});

test('serviceNames returns all 3 types', function () {
    $service = new SearchService;
    $names = $service->serviceNames();

    expect($names)->toHaveCount(3);
    expect($names)->toHaveKeys(['dog-grooming', 'dog-walking', 'cat-sitting']);
    expect($names['dog-grooming'])->toBe('Dog Grooming');
});
