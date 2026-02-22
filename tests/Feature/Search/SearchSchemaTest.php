<?php

use App\Models\Business;
use App\Models\GeocodeCache;
use App\Models\Location;

beforeEach(function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);
    GeocodeCache::create(['slug' => 'fulham-london', 'name' => 'Fulham', 'display_name' => 'Fulham, London', 'latitude' => 51.4749, 'longitude' => -0.2010]);
});

test('landing page includes SearchResultsPage schema', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertSee('SearchResultsPage', false);
});

test('landing page has correct meta title', function () {
    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertSee('<title>Dog Grooming in London - Find &amp; Book | heyBertie</title>', false);
});

test('landing page has canonical URL', function () {
    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertSee('<link rel="canonical"', false);
});

test('town-level landing page includes town in title', function () {
    $this->get('/dog-grooming-in-fulham-london')
        ->assertSuccessful()
        ->assertSee('Fulham, London', false);
});
