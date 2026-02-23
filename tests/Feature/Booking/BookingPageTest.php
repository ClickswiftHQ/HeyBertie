<?php

use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\StaffMember;

it('renders the booking page for a bookable business', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id, 'name' => 'Full Groom']);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertSuccessful()
        ->assertSee('Full Groom')
        ->assertSee($business->name);
});

it('returns 404 for a free tier business', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertNotFound();
});

it('returns 404 for a location that does not accept bookings', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create([
        'business_id' => $business->id,
        'accepts_bookings' => false,
    ]);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertNotFound();
});

it('returns 404 for an inactive location', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->inactive()->create(['business_id' => $business->id]);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertNotFound();
});

it('shows only active services for the location', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id, 'name' => 'Active Service', 'is_active' => true]);
    Service::factory()->create(['business_id' => $business->id, 'name' => 'Inactive Service', 'is_active' => false]);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertSuccessful()
        ->assertSee('Active Service')
        ->assertDontSee('Inactive Service');
});

it('does not show staff step when staff selection is disabled', function () {
    $business = Business::factory()->solo()->completed()->verified()->create([
        'settings' => ['staff_selection_enabled' => false],
    ]);
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id]);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertSuccessful()
        ->assertSee('staffSelectionEnabled: false', false);
});

it('includes staff data when staff selection is enabled', function () {
    $business = Business::factory()->salon()->completed()->verified()->create([
        'settings' => ['staff_selection_enabled' => true],
    ]);
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id]);
    $staff = StaffMember::factory()->create([
        'business_id' => $business->id,
        'display_name' => 'Sarah',
        'accepts_online_bookings' => true,
    ]);
    $staff->locations()->attach($location->id);

    $this->get(route('booking.show', [$business->handle, $location->slug]))
        ->assertSuccessful()
        ->assertSee('Sarah');
});

it('renders the confirmation page with a valid booking reference', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = \App\Models\Customer::factory()->create(['business_id' => $business->id]);

    $booking = \App\Models\Booking::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'booking_reference' => 'BK-TEST01',
    ]);

    $this->get(route('booking.confirmation', [
        'handle' => $business->handle,
        'locationSlug' => $location->slug,
        'ref' => 'BK-TEST01',
    ]))
        ->assertSuccessful()
        ->assertSee('BK-TEST01')
        ->assertSee('Booking Confirmed');
});

it('returns 404 for an invalid booking reference', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get(route('booking.confirmation', [
        'handle' => $business->handle,
        'locationSlug' => $location->slug,
        'ref' => 'BK-INVALID',
    ]))
        ->assertNotFound();
});
