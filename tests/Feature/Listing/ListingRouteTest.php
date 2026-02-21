<?php

use App\Models\Business;
use App\Models\HandleChange;
use App\Models\Location;

test('single-location business redirects from handle to location URL', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle)
        ->assertRedirect('/'.$business->handle.'/'.$location->slug)
        ->assertStatus(301);
});

test('single-location listing page returns 200 at location URL', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle.'/'.$location->slug)
        ->assertSuccessful()
        ->assertViewIs('listing.show');
});

test('listing page returns 404 for non-existent handle', function () {
    $this->get('/nonexistent-handle')
        ->assertNotFound();
});

test('listing page returns 404 for inactive business', function () {
    $business = Business::factory()->completed()->create(['is_active' => false]);
    Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle)
        ->assertNotFound();
});

test('listing page returns 404 for draft business (onboarding not completed)', function () {
    $business = Business::factory()->create(['onboarding_completed' => false]);
    Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle)
        ->assertNotFound();
});

test('old handle returns 301 redirect to new handle', function () {
    $business = Business::factory()->completed()->create(['handle' => 'new-handle']);
    Location::factory()->create(['business_id' => $business->id]);

    HandleChange::create([
        'business_id' => $business->id,
        'old_handle' => 'old-handle',
        'new_handle' => 'new-handle',
        'changed_by_user_id' => $business->owner_user_id,
        'changed_at' => now(),
    ]);

    $this->get('/old-handle')
        ->assertRedirect('/new-handle')
        ->assertStatus(301);
});

test('canonical URL redirects to handle URL', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id]);

    $this->get('/p/'.$business->slug.'-'.$business->id)
        ->assertRedirect('/'.$business->handle)
        ->assertStatus(301);
});

test('canonical URL redirects regardless of slug', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id]);

    $this->get('/p/any-wrong-slug-'.$business->id)
        ->assertRedirect('/'.$business->handle)
        ->assertStatus(301);
});

test('old @handle URL redirects to clean handle URL', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id]);

    $this->get('/@'.$business->handle)
        ->assertRedirect('/'.$business->handle)
        ->assertStatus(301);
});

test('old @handle location URL redirects to clean URL', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create([
        'business_id' => $business->id,
        'slug' => 'fulham-salon',
    ]);

    $this->get('/@'.$business->handle.'/fulham-salon')
        ->assertRedirect('/'.$business->handle.'/fulham-salon')
        ->assertStatus(301);
});

test('listing page returns 200 for valid location slug', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create([
        'business_id' => $business->id,
        'slug' => 'fulham-salon',
    ]);

    $this->get('/'.$business->handle.'/fulham-salon')
        ->assertSuccessful()
        ->assertViewIs('listing.show')
        ->assertViewHas('location', fn ($loc) => $loc->id === $location->id);
});

test('listing page returns 404 for invalid location slug', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle.'/nonexistent-location')
        ->assertNotFound();
});

test('multi-location business shows hub page at handle URL', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-a']);
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-b', 'is_primary' => false]);

    $this->get('/'.$business->handle)
        ->assertSuccessful()
        ->assertViewIs('listing.hub');
});

test('multi-location hub has all locations', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-a']);
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'loc-b', 'is_primary' => false]);

    $this->get('/'.$business->handle)
        ->assertSuccessful()
        ->assertViewHas('locations', fn ($l) => $l->count() === 2);
});
