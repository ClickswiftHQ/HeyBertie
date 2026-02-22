<?php

use App\Models\Business;
use App\Models\GeocodeCache;
use App\Models\Location;
use App\Services\GeocodingService;

beforeEach(function () {
    $this->geocodingMock = $this->mock(GeocodingService::class);

    // Seed geocode_cache for landing page tests
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);
    GeocodeCache::create(['slug' => 'fulham-london', 'name' => 'Fulham', 'display_name' => 'Fulham, London', 'latitude' => 51.4749, 'longitude' => -0.2010]);
});

test('search with known location redirects to SEO landing page', function () {
    $this->get('/search?location=London&service=dog-grooming')
        ->assertRedirect('/dog-grooming-in-london');
});

test('search with known location preserves filters in redirect', function () {
    $this->get('/search?location=London&service=dog-grooming&sort=rating&type=salon')
        ->assertRedirect('/dog-grooming-in-london?sort=rating&type=salon');
});

test('search with unknown location returns 200', function () {
    $this->geocodingMock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD')
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
        'latitude' => 51.4823,
        'longitude' => -0.1953,
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 0);
});

test('draft businesses excluded from results', function () {
    $business = Business::factory()->create(['onboarding_completed' => false]);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.4823,
        'longitude' => -0.1953,
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 0);
});

test('type filter works', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create([
        'business_id' => $business->id,
        'location_type' => 'salon',
        'latitude' => 51.4823,
        'longitude' => -0.1953,
    ]);
    Location::factory()->mobile()->secondary()->create([
        'business_id' => $business->id,
        'latitude' => 51.4830,
        'longitude' => -0.1960,
    ]);

    $this->geocodingMock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD&type=salon')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 1);
});

test('sort param is accepted', function () {
    $this->geocodingMock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD&sort=rating')
        ->assertSuccessful()
        ->assertViewHas('sort', 'rating');
});
