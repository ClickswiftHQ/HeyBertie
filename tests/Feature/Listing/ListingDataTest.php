<?php

use App\Models\Business;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;

test('business data is loaded with correct relationships', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id, 'is_active' => true]);
    Review::factory()->create(['business_id' => $business->id, 'is_published' => true]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewIs('listing.show')
        ->assertViewHas('business')
        ->assertViewHas('location')
        ->assertViewHas('services', fn ($s) => $s->count() === 1)
        ->assertViewHas('reviews', fn ($r) => $r->count() === 1)
        ->assertViewHas('rating');
});

test('only active services are returned', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id, 'is_active' => true]);
    Service::factory()->create(['business_id' => $business->id, 'is_active' => false]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewHas('services', fn ($s) => $s->count() === 1);
});

test('only published reviews are returned', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Review::factory()->create(['business_id' => $business->id, 'is_published' => true]);
    Review::factory()->create(['business_id' => $business->id, 'is_published' => false]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewHas('reviews', fn ($r) => $r->count() === 1);
});

test('reviews are limited to 10', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Review::factory()->count(15)->create(['business_id' => $business->id, 'is_published' => true]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewHas('reviews', fn ($r) => $r->count() === 10)
        ->assertViewHas('hasMoreReviews', true);
});

test('rating aggregation is calculated correctly', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Review::factory()->create(['business_id' => $business->id, 'rating' => 5, 'is_published' => true]);
    Review::factory()->create(['business_id' => $business->id, 'rating' => 3, 'is_published' => true]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewHas('rating', fn ($r) => $r['average'] == 4
            && $r['count'] === 2
            && $r['breakdown'][5] === 1
            && $r['breakdown'][3] === 1
            && $r['breakdown'][1] === 0
        );
});

test('multi-location filters services by location', function () {
    $business = Business::factory()->completed()->create();
    $location1 = Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-a']);
    $location2 = Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-b', 'is_primary' => false]);

    Service::factory()->create(['business_id' => $business->id, 'location_id' => $location1->id, 'is_active' => true, 'name' => 'Loc1 Service']);
    Service::factory()->create(['business_id' => $business->id, 'location_id' => $location2->id, 'is_active' => true, 'name' => 'Loc2 Service']);
    Service::factory()->create(['business_id' => $business->id, 'location_id' => null, 'is_active' => true, 'name' => 'Global Service']);

    $this->get('/'.$business->handle.'/loc-b')
        ->assertSuccessful()
        ->assertViewHas('services', fn ($s) => $s->count() === 2);
});

test('location switcher data includes all active locations', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id, 'is_active' => true]);
    Location::factory()->create(['business_id' => $business->id, 'is_active' => true, 'is_primary' => false]);
    Location::factory()->create(['business_id' => $business->id, 'is_active' => false, 'is_primary' => false]);

    // Multi-location: hub page also has locations
    $this->get('/'.$business->handle)
        ->assertSuccessful()
        ->assertViewHas('locations', fn ($l) => $l->count() === 2);
});

test('hub page returns business and locations data', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-a']);
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-b', 'is_primary' => false]);

    $this->get('/'.$business->handle)
        ->assertSuccessful()
        ->assertViewIs('listing.hub')
        ->assertViewHas('business')
        ->assertViewHas('locations', fn ($l) => $l->count() === 2)
        ->assertViewHas('rating')
        ->assertViewHas('schemaMarkup')
        ->assertViewHas('canonicalUrl');
});

test('single-location canonical URL points to location URL', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewHas('canonicalUrl', fn ($url) => str_ends_with($url, '/'.$business->handle.'/'.$location->slug));
});

test('location page has self-referencing canonical URL', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-a']);
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-b', 'is_primary' => false]);

    $this->get('/'.$business->handle.'/loc-b')
        ->assertSuccessful()
        ->assertViewHas('canonicalUrl', fn ($url) => str_ends_with($url, '/'.$business->handle.'/loc-b'));
});
