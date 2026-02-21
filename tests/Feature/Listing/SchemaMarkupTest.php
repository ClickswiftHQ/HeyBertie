<?php

use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Services\SchemaMarkupService;

test('LocalBusiness schema is generated with correct structure', function () {
    $business = Business::factory()->completed()->create([
        'name' => 'Test Grooming',
        'phone' => '020 7123 4567',
        'email' => 'test@example.com',
    ]);
    $location = Location::factory()->create([
        'business_id' => $business->id,
        'city' => 'London',
        'postcode' => 'SW6 3JJ',
    ]);

    $service = new SchemaMarkupService;
    $schema = $service->generateForListing($business, $location, collect(), null, 0);

    expect($schema)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'LocalBusiness')
        ->toHaveKey('name', 'Test Grooming')
        ->toHaveKey('telephone', '020 7123 4567')
        ->toHaveKey('email', 'test@example.com')
        ->toHaveKey('address')
        ->and($schema['url'])->toContain('/'.$business->handle);

    expect($schema['address'])
        ->toHaveKey('@type', 'PostalAddress')
        ->toHaveKey('addressLocality', 'London')
        ->toHaveKey('postalCode', 'SW6 3JJ');
});

test('AggregateRating is only included when reviews exist', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $service = new SchemaMarkupService;

    $schemaNoReviews = $service->generateForListing($business, $location, collect(), null, 0);
    expect($schemaNoReviews)->not->toHaveKey('aggregateRating');

    $schemaWithReviews = $service->generateForListing($business, $location, collect(), 4.5, 10);
    expect($schemaWithReviews)
        ->toHaveKey('aggregateRating')
        ->and($schemaWithReviews['aggregateRating'])->toHaveKey('ratingValue', '4.5');
});

test('services are included in schema', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $services = Service::factory()->count(2)->create([
        'business_id' => $business->id,
        'is_active' => true,
    ]);

    $service = new SchemaMarkupService;
    $schema = $service->generateForListing($business, $location, $services, null, 0);

    expect($schema)
        ->toHaveKey('hasOfferCatalog')
        ->and($schema['hasOfferCatalog']['itemListElement'])->toHaveCount(2);
});

test('opening hours are generated from location hours', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create([
        'business_id' => $business->id,
        'opening_hours' => [
            'monday' => ['open' => '09:00', 'close' => '17:00'],
            'tuesday' => ['open' => '09:00', 'close' => '17:00'],
            'sunday' => null,
        ],
    ]);

    $service = new SchemaMarkupService;
    $schema = $service->generateForListing($business, $location, collect(), null, 0);

    expect($schema)
        ->toHaveKey('openingHoursSpecification')
        ->and($schema['openingHoursSpecification'])->toHaveCount(2)
        ->and($schema['openingHoursSpecification'][0])->toHaveKey('dayOfWeek', 'Monday');
});

test('hub generates Organization schema with subOrganization', function () {
    $business = Business::factory()->completed()->create(['name' => 'Muddy Paws']);
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'fulham', 'name' => 'Fulham Salon']);
    Location::factory()->create(['business_id' => $business->id, 'slug' => 'chelsea', 'name' => 'Chelsea Salon', 'is_primary' => false]);

    $business->load(['locations' => fn ($q) => $q->where('is_active', true)]);

    $service = new SchemaMarkupService;
    $schema = $service->generateForHub($business);

    expect($schema)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'Organization')
        ->toHaveKey('name', 'Muddy Paws')
        ->toHaveKey('subOrganization')
        ->and($schema['subOrganization'])->toHaveCount(2)
        ->and($schema['subOrganization'][0])->toHaveKey('@type', 'LocalBusiness')
        ->and($schema['url'])->toContain('/'.$business->handle);
});

test('location page generates LocalBusiness with branchOf', function () {
    $business = Business::factory()->completed()->create(['name' => 'Muddy Paws']);
    $location = Location::factory()->create(['business_id' => $business->id, 'slug' => 'fulham']);

    $service = new SchemaMarkupService;
    $schema = $service->generateForListing($business, $location, collect(), null, 0, isMultiLocation: true);

    expect($schema)
        ->toHaveKey('@type', 'LocalBusiness')
        ->toHaveKey('branchOf')
        ->and($schema['branchOf'])->toHaveKey('@type', 'Organization')
        ->and($schema['branchOf'])->toHaveKey('name', 'Muddy Paws')
        ->and($schema['url'])->toContain('/'.$business->handle.'/fulham');
});

test('single-location listing does not have branchOf', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $service = new SchemaMarkupService;
    $schema = $service->generateForListing($business, $location, collect(), null, 0, isMultiLocation: false);

    expect($schema)
        ->not->toHaveKey('branchOf')
        ->and($schema['url'])->toContain('/'.$business->handle);
});
