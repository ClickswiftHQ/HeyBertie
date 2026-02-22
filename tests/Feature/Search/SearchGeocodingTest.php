<?php

use App\Models\AddressCache;
use App\Models\GeocodeCache;
use App\Models\UnmatchedSearch;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Http;

test('city name search resolves from geocode_cache', function () {
    Http::fake();

    GeocodeCache::create([
        'slug' => 'london',
        'name' => 'London',
        'display_name' => 'London',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'settlement_type' => 'City',
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('London');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(51.5074)
        ->longitude->toBe(-0.1278);

    Http::assertNothingSent();
});

test('city name search is case insensitive', function () {
    Http::fake();

    GeocodeCache::create([
        'slug' => 'manchester',
        'name' => 'Manchester',
        'display_name' => 'Manchester',
        'latitude' => 53.4808,
        'longitude' => -2.2426,
        'settlement_type' => 'City',
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('MANCHESTER');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(53.4808)
        ->longitude->toBe(-2.2426);

    Http::assertNothingSent();
});

test('postcode search checks address_cache first', function () {
    Http::fake();

    AddressCache::create([
        'postcode' => 'SW1A 1AA',
        'line_1' => 'Buckingham Palace',
        'line_2' => '',
        'line_3' => '',
        'post_town' => 'LONDON',
        'county' => '',
        'latitude' => 51.50100000,
        'longitude' => -0.14200000,
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('SW1A 1AA');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(51.501)
        ->longitude->toBe(-0.142);

    Http::assertNothingSent();
});

test('postcode search falls back to API when not in cache', function () {
    Http::fake([
        'api.ideal-postcodes.co.uk/*' => Http::response([
            'code' => 2000,
            'result' => [
                [
                    'line_1' => '10 Downing Street',
                    'line_2' => '',
                    'line_3' => '',
                    'post_town' => 'LONDON',
                    'county' => '',
                    'postcode' => 'SW1A 2AA',
                    'latitude' => 51.5034,
                    'longitude' => -0.1276,
                ],
            ],
        ]),
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('SW1A 2AA');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(51.5034)
        ->longitude->toBe(-0.1276);
});

test('API results are cached in address_cache', function () {
    Http::fake([
        'api.ideal-postcodes.co.uk/*' => Http::response([
            'code' => 2000,
            'result' => [
                [
                    'line_1' => '10 Downing Street',
                    'line_2' => '',
                    'line_3' => '',
                    'post_town' => 'LONDON',
                    'county' => '',
                    'postcode' => 'SW1A 2AA',
                    'latitude' => 51.5034,
                    'longitude' => -0.1276,
                ],
            ],
        ]),
    ]);

    $service = app(GeocodingService::class);
    $service->geocode('SW1A 2AA');

    expect(AddressCache::where('postcode', 'SW1A 2AA')->count())->toBe(1);
});

test('unmatched searches are logged', function () {
    Http::fake([
        'api.ideal-postcodes.co.uk/*' => Http::response([
            'code' => 4040,
            'message' => 'Postcode not found',
        ], 404),
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('Nonexistent Place');

    expect($result)->toBeNull();
    expect(UnmatchedSearch::where('query', 'nonexistent place')->first())
        ->not->toBeNull()
        ->search_count->toBe(1);
});

test('unmatched search count increments on repeat', function () {
    Http::fake([
        'api.ideal-postcodes.co.uk/*' => Http::response([
            'code' => 4040,
            'message' => 'Postcode not found',
        ], 404),
    ]);

    $service = app(GeocodingService::class);
    $service->geocode('Nonexistent Place');
    $service->geocode('Nonexistent Place');

    expect(UnmatchedSearch::where('query', 'nonexistent place')->first())
        ->search_count->toBe(2);
});

test('postcode geocoding falls back to sector match when API unavailable', function () {
    Http::fake();
    config()->set('services.ideal_postcodes.api_key', null);

    GeocodeCache::create([
        'slug' => 'aarons-hill-surrey',
        'name' => "Aaron's Hill",
        'display_name' => "Aaron's Hill, Surrey",
        'latitude' => 51.18291,
        'longitude' => -0.63098,
        'postcode_sector' => 'GU7 2',
        'settlement_type' => 'Suburban Area',
        'county' => 'Surrey',
        'country' => 'England',
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('GU7 2AB');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(51.18291)
        ->longitude->toBe(-0.63098);

    Http::assertNothingSent();
});
