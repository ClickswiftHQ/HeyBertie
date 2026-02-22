<?php

use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Services\GeocodingService;

beforeEach(function () {
    $this->geocodingMock = $this->mock(GeocodingService::class);
});

test('distance filtering excludes far locations', function () {
    $business = Business::factory()->completed()->create();

    // Near location (London center)
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'town' => 'Westminster',
        'city' => 'London',
    ]);

    // Far location (Manchester)
    $farBusiness = Business::factory()->completed()->create();
    Location::factory()->create([
        'business_id' => $farBusiness->id,
        'latitude' => 53.4808,
        'longitude' => -2.2426,
        'town' => 'Manchester',
        'city' => 'Manchester',
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London&distance=5')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 1);
});

test('pagination returns 12 on page 1 and remainder on page 2', function () {
    // Create 15 active businesses with nearby locations
    for ($i = 0; $i < 15; $i++) {
        $business = Business::factory()->completed()->create();
        Location::factory()->create([
            'business_id' => $business->id,
            'latitude' => 51.5074 + ($i * 0.001),
            'longitude' => -0.1278,
        ]);
    }

    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $response = $this->get('/search?location=London&distance=50');
    $response->assertSuccessful();
    $results = $response->viewData('results');
    expect($results)->toHaveCount(12);
    expect($results->total())->toBe(15);

    $response2 = $this->get('/search?location=London&distance=50&page=2');
    $response2->assertSuccessful();
    $results2 = $response2->viewData('results');
    expect($results2)->toHaveCount(3);
});

test('result cards contain expected business data', function () {
    $business = Business::factory()->completed()->verified()->create(['name' => 'Muddy Paws Grooming']);
    $location = Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'town' => 'Westminster',
        'city' => 'London',
    ]);
    Service::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'name' => 'Full Groom',
        'price' => 45.00,
        'price_type' => 'fixed',
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London')
        ->assertSuccessful()
        ->assertSee('Muddy Paws Grooming')
        ->assertSee('Full Groom')
        ->assertSee('Westminster');
});
