<?php

use App\Services\GeocodingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

test('city name search resolves to coordinates without API call', function () {
    Http::fake();

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

    $service = app(GeocodingService::class);
    $result = $service->geocode('MANCHESTER');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(53.4808)
        ->longitude->toBe(-2.2426);

    Http::assertNothingSent();
});

test('postcode search checks local table first', function () {
    Http::fake();

    DB::table('postcodes')->insert([
        'postcode' => 'SW1A 1AA',
        'latitude' => 51.50100000,
        'longitude' => -0.14200000,
        'town' => 'London',
        'county' => 'Greater London',
        'region' => null,
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('SW1A 1AA');

    expect($result)
        ->not->toBeNull()
        ->latitude->toBe(51.501)
        ->longitude->toBe(-0.142);

    Http::assertNothingSent();
});

test('postcode search falls back to API when not in local table', function () {
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

test('invalid input returns null', function () {
    Http::fake([
        'api.ideal-postcodes.co.uk/*' => Http::response([
            'code' => 4040,
            'message' => 'Postcode not found',
        ], 404),
    ]);

    $service = app(GeocodingService::class);
    $result = $service->geocode('Nonexistent Place');

    expect($result)->toBeNull();
});
