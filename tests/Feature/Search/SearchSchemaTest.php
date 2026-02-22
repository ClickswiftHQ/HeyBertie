<?php

use App\Models\Business;
use App\Models\Location;

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
