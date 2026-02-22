<?php

use App\Models\Business;
use App\Models\Location;
use App\Services\GeocodingService;

beforeEach(function () {
    $this->geocodingMock = $this->mock(GeocodingService::class);
});

test('search with valid location returns 200', function () {
    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London')
        ->assertSuccessful()
        ->assertViewIs('search.results')
        ->assertViewHas('isLandingPage', false);
});

test('search without location redirects with validation error', function () {
    $this->get('/search')
        ->assertRedirect();
});

test('geocoding failure shows error state gracefully', function () {
    $this->geocodingMock->shouldReceive('geocode')
        ->with('Nonexistent Place')
        ->andReturn(null);

    $this->get('/search?location=Nonexistent+Place')
        ->assertSuccessful()
        ->assertViewIs('search.results')
        ->assertViewHas('geocodingFailed', true);
});

test('landing page dog-grooming-in-london returns 200', function () {
    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertViewIs('search.results')
        ->assertViewHas('isLandingPage', true);
});

test('town-level landing page dog-grooming-in-fulham-london returns 200', function () {
    $this->get('/dog-grooming-in-fulham-london')
        ->assertSuccessful()
        ->assertViewIs('search.results')
        ->assertViewHas('isLandingPage', true);
});

test('invalid city returns 404', function () {
    $this->get('/dog-grooming-in-invalid-city')
        ->assertNotFound();
});

test('invalid service returns 404', function () {
    $this->get('/invalid-service-in-london')
        ->assertNotFound();
});

test('inactive businesses excluded from results', function () {
    $business = Business::factory()->completed()->create(['is_active' => false]);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 0);
});

test('draft businesses excluded from results', function () {
    $business = Business::factory()->create(['onboarding_completed' => false]);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 0);
});

test('type filter works', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create([
        'business_id' => $business->id,
        'location_type' => 'salon',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);
    Location::factory()->mobile()->secondary()->create([
        'business_id' => $business->id,
        'latitude' => 51.5080,
        'longitude' => -0.1280,
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London&type=salon')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 1);
});

test('sort param is accepted', function () {
    $this->geocodingMock->shouldReceive('geocode')
        ->with('London')
        ->andReturn(['latitude' => 51.5074, 'longitude' => -0.1278]);

    $this->get('/search?location=London&sort=rating')
        ->assertSuccessful()
        ->assertViewHas('sort', 'rating');
});
