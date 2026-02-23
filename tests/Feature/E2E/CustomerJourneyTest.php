<?php

/**
 * Flow 1: Customer Journey E2E Tests
 *
 * Covers: homepage, location autocomplete, search → SEO redirect, postcode search,
 * SEO landing pages, popular links, business listing, filters, edge cases.
 */

use App\Models\Business;
use App\Models\GeocodeCache;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Services\GeocodingService;

// ─── 1.1 Homepage ─────────────────────────────────────────────────

test('homepage loads successfully', function () {
    $this->get('/')
        ->assertSuccessful();
});

test('homepage has search form with service dropdown', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('name="service"', false)
        ->assertSee('Dog Grooming')
        ->assertSee('Dog Walking')
        ->assertSee('Cat Sitting');
});

test('homepage has location autocomplete input', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('name="location"', false)
        ->assertSee('locationAutocomplete', false);
});

test('homepage has date picker', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('type="date"', false);
});

test('homepage has search button', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('type="submit"', false)
        ->assertSee('Search');
});

test('homepage has popular quick links', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Popular:')
        ->assertSee('href="/dog-grooming-in-london"', false)
        ->assertSee('href="/dog-grooming-in-manchester"', false)
        ->assertSee('href="/dog-grooming-in-birmingham"', false)
        ->assertSee('href="/dog-grooming-in-leeds"', false)
        ->assertSee('href="/dog-grooming-in-bristol"', false);
});

test('homepage has popular cities grid section', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Popular cities')
        ->assertSee('London')
        ->assertSee('Manchester')
        ->assertSee('Birmingham')
        ->assertSee('Edinburgh')
        ->assertSee('Glasgow');
});

// ─── 1.2 Location Autocomplete ────────────────────────────────────

test('autocomplete returns suggestions for partial city input', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->getJson('/api/search-suggest?q=Lon')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'London']);
});

test('autocomplete returns Fulham London for partial input', function () {
    GeocodeCache::create(['slug' => 'fulham-london', 'name' => 'Fulham', 'display_name' => 'Fulham, London', 'latitude' => 51.4749, 'longitude' => -0.2010, 'settlement_type' => 'Suburban Area', 'county' => 'London']);

    $this->getJson('/api/search-suggest?q=Ful')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Fulham, London']);
});

test('autocomplete returns postcode sector matches for postcode input', function () {
    GeocodeCache::create([
        'slug' => 'godalming-surrey',
        'name' => 'Godalming',
        'display_name' => 'Godalming, Surrey',
        'latitude' => 51.1860,
        'longitude' => -0.6116,
        'settlement_type' => 'Town',
        'postcode_sector' => 'GU7 1',
    ]);

    $this->getJson('/api/search-suggest?q=GU7')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Godalming, Surrey']);
});

test('autocomplete returns results even for single character', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    // The API returns results for any non-empty input; min 2-char restriction is client-side only
    $this->getJson('/api/search-suggest?q=L')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'London']);
});

test('autocomplete returns empty for empty query', function () {
    $this->getJson('/api/search-suggest?q=')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

// ─── 1.3 Search → SEO Landing Page Redirect ──────────────────────

test('search with Dog Grooming and London redirects to landing page', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->get('/search?service=dog-grooming&location=London')
        ->assertRedirect('/dog-grooming-in-london');
});

test('landing page shows heading with service and location', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertSee('Dog Grooming in London');
});

test('landing page shows seeded Muddy Paws business near London', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $business = Business::factory()->completed()->verified()->create(['name' => 'Muddy Paws Grooming']);
    $location = Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.4823,
        'longitude' => -0.1953,
        'town' => 'Fulham',
        'city' => 'London',
    ]);
    Service::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'name' => 'Full Groom',
        'price' => 45.00,
    ]);

    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertSee('Muddy Paws Grooming');
});

// ─── 1.4 Search → Postcode (Non-Redirect) ─────────────────────────

test('postcode search does not redirect', function () {
    $mock = $this->mock(GeocodingService::class);
    $mock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD')
        ->assertSuccessful()
        ->assertViewIs('search.results')
        ->assertViewHas('isLandingPage', false);
});

test('search results page has working autocomplete', function () {
    $mock = $this->mock(GeocodingService::class);
    $mock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $this->get('/search?location=SW6+1UD')
        ->assertSuccessful()
        ->assertSee('name="location"', false)
        ->assertSee('locationAutocomplete', false);
});

// ─── 1.5 SEO Landing Pages (Direct URL) ──────────────────────────

test('dog-grooming-in-london returns 200', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->get('/dog-grooming-in-london')
        ->assertSuccessful();
});

test('dog-grooming-in-fulham-london returns 200', function () {
    GeocodeCache::create(['slug' => 'fulham-london', 'name' => 'Fulham', 'display_name' => 'Fulham, London', 'latitude' => 51.4749, 'longitude' => -0.2010]);

    $this->get('/dog-grooming-in-fulham-london')
        ->assertSuccessful();
});

test('dog-grooming-in-manchester returns 200', function () {
    GeocodeCache::create(['slug' => 'manchester', 'name' => 'Manchester', 'display_name' => 'Manchester', 'latitude' => 53.4808, 'longitude' => -2.2426, 'settlement_type' => 'City']);

    $this->get('/dog-grooming-in-manchester')
        ->assertSuccessful();
});

test('dog-grooming-in-invalid-city returns 404', function () {
    $this->get('/dog-grooming-in-invalid-city')
        ->assertNotFound();
});

test('invalid-service-in-london returns 404', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->get('/invalid-service-in-london')
        ->assertNotFound();
});

// ─── 1.6 Popular Links ───────────────────────────────────────────

test('popular quick link London goes to dog-grooming-in-london', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('href="/dog-grooming-in-london"', false);
});

test('popular cities grid Manchester links to dog-grooming-in-manchester', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('href="/dog-grooming-in-manchester"', false);
});

// ─── 1.7 Business Listing Page ────────────────────────────────────

test('listing page shows business name and description', function () {
    $business = Business::factory()->completed()->create([
        'name' => 'Muddy Paws Grooming',
        'description' => 'Professional dog grooming in South West London.',
    ]);
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertSee('Muddy Paws Grooming')
        ->assertSee('Professional dog grooming in South West London.');
});

test('listing page shows services', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'name' => 'Full Groom',
        'price' => 45.00,
        'is_active' => true,
    ]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertSee('Full Groom');
});

test('listing page shows reviews and rating', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Review::factory()->create([
        'business_id' => $business->id,
        'rating' => 5,
        'review_text' => 'Excellent grooming service!',
        'is_published' => true,
    ]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertSee('Excellent grooming service!')
        ->assertSee('5.0');
});

test('multi-location business shows hub page with all locations', function () {
    $business = Business::factory()->completed()->create(['name' => 'Muddy Paws Grooming']);
    Location::factory()->create(['business_id' => $business->id, 'name' => 'Fulham Salon', 'slug' => 'fulham-london']);
    Location::factory()->create(['business_id' => $business->id, 'name' => 'Chelsea Branch', 'slug' => 'chelsea-london', 'is_primary' => false]);

    $this->get('/'.$business->handle)
        ->assertSuccessful()
        ->assertViewIs('listing.hub')
        ->assertSee('Fulham Salon')
        ->assertSee('Chelsea Branch');
});

// ─── 1.8 Filters ─────────────────────────────────────────────────

test('type filter salon only shows salon results', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $salonBusiness = Business::factory()->completed()->create();
    Location::factory()->create([
        'business_id' => $salonBusiness->id,
        'location_type' => 'salon',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $mobileBusiness = Business::factory()->completed()->create();
    Location::factory()->mobile()->secondary()->create([
        'business_id' => $mobileBusiness->id,
        'latitude' => 51.5080,
        'longitude' => -0.1280,
    ]);

    $this->get('/dog-grooming-in-london?type=salon')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 1);
});

test('sort by rating is accepted on landing page', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->get('/dog-grooming-in-london?sort=rating')
        ->assertSuccessful()
        ->assertViewHas('sort', 'rating');
});

test('search redirect preserves filters', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $this->get('/search?location=London&service=dog-grooming&sort=rating&type=salon')
        ->assertRedirect('/dog-grooming-in-london?sort=rating&type=salon');
});

// ─── 1.9 Edge Cases ──────────────────────────────────────────────

test('empty location field shows validation error', function () {
    $this->get('/search')
        ->assertRedirect();
});

test('unknown location shows graceful error state', function () {
    $mock = $this->mock(GeocodingService::class);
    $mock->shouldReceive('geocode')
        ->with('xyznonexistent')
        ->andReturn(null);

    $this->get('/search?location=xyznonexistent')
        ->assertSuccessful()
        ->assertViewIs('search.results')
        ->assertViewHas('geocodingFailed', true)
        ->assertSee("couldn't find that location", false);
});
