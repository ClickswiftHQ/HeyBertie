<?php

use App\Models\Business;
use App\Models\Location;
use App\Models\ServiceArea;

it('belongs to a business', function () {
    $location = Location::factory()->create();

    expect($location->business)->toBeInstanceOf(Business::class);
});

it('scopes to active locations', function () {
    Location::factory()->count(2)->create(['is_active' => true]);
    Location::factory()->inactive()->create();

    expect(Location::active()->count())->toBe(2);
});

it('scopes to accepting bookings', function () {
    Location::factory()->create(['is_active' => true, 'accepts_bookings' => true]);
    Location::factory()->create(['is_active' => true, 'accepts_bookings' => false]);
    Location::factory()->inactive()->create();

    expect(Location::acceptingBookings()->count())->toBe(1);
});

it('scopes to primary locations', function () {
    Location::factory()->create(['is_primary' => true]);
    Location::factory()->secondary()->create();

    expect(Location::primary()->count())->toBe(1);
});

it('scopes to mobile locations', function () {
    Location::factory()->mobile()->create();
    Location::factory()->create(['is_mobile' => false]);

    expect(Location::mobile()->count())->toBe(1);
});

it('calculates distance between coordinates', function () {
    $location = Location::factory()->create([
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    // Distance from central London to roughly Heathrow (~22km)
    $distance = $location->getDistanceFrom(51.4700, -0.4543);

    expect($distance)->toBeGreaterThan(20)
        ->and($distance)->toBeLessThan(30);
});

it('checks service radius for mobile locations', function () {
    $location = Location::factory()->mobile()->create([
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'service_radius_km' => 10,
    ]);

    // Close location (should be within radius)
    expect($location->isWithinServiceRadius(51.51, -0.12))->toBeTrue();

    // Far location (should be outside radius)
    expect($location->isWithinServiceRadius(52.0, -1.0))->toBeFalse();
});

it('has service areas for mobile locations', function () {
    $location = Location::factory()->mobile()->create();

    ServiceArea::create(['location_id' => $location->id, 'area_name' => 'Fulham', 'postcode_prefix' => 'SW']);
    ServiceArea::create(['location_id' => $location->id, 'area_name' => 'Chelsea', 'postcode_prefix' => 'SW']);

    expect($location->serviceAreas)->toHaveCount(2);
});

it('casts opening_hours to array', function () {
    $hours = ['mon' => ['open' => '09:00', 'close' => '17:00']];
    $location = Location::factory()->create(['opening_hours' => $hours]);

    expect($location->opening_hours)->toBeArray()
        ->and($location->opening_hours['mon']['open'])->toBe('09:00');
});
