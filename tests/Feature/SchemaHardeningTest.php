<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\StaffMember;

// --- Soft Deletes ---

it('soft deletes a booking', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    $booking = Booking::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    $booking->delete();

    expect(Booking::find($booking->id))->toBeNull()
        ->and(Booking::withTrashed()->find($booking->id))->not->toBeNull();
});

it('soft deletes a customer', function () {
    $business = Business::factory()->solo()->verified()->create();
    $customer = Customer::factory()->create(['business_id' => $business->id]);

    $customer->delete();

    expect(Customer::find($customer->id))->toBeNull()
        ->and(Customer::withTrashed()->find($customer->id))->not->toBeNull();
});

it('soft deletes a service', function () {
    $business = Business::factory()->solo()->verified()->create();
    $service = Service::factory()->create(['business_id' => $business->id]);

    $service->delete();

    expect(Service::find($service->id))->toBeNull()
        ->and(Service::withTrashed()->find($service->id))->not->toBeNull();
});

it('soft deletes a staff member', function () {
    $business = Business::factory()->solo()->verified()->create();
    $staff = StaffMember::factory()->create(['business_id' => $business->id]);

    $staff->delete();

    expect(StaffMember::find($staff->id))->toBeNull()
        ->and(StaffMember::withTrashed()->find($staff->id))->not->toBeNull();
});

// --- Customer.updateFromBooking() batched query ---

it('updates customer stats from booking in a single query', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'total_bookings' => 5,
        'total_spent' => 200.00,
        'loyalty_points' => 50,
        'last_visit' => null,
    ]);

    $booking = Booking::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'price' => 45.00,
        'appointment_datetime' => now()->subDay(),
    ]);

    $customer->updateFromBooking($booking);

    expect($customer->total_bookings)->toBe(6)
        ->and((float) $customer->total_spent)->toBe(245.00)
        ->and($customer->loyalty_points)->toBe(60)
        ->and($customer->last_visit->toDateTimeString())
        ->toBe($booking->appointment_datetime->toDateTimeString());
});

it('updates customer stats from multiple bookings cumulatively', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'total_bookings' => 0,
        'total_spent' => 0,
        'loyalty_points' => 0,
    ]);

    $bookings = Booking::factory()->count(3)->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'price' => 30.00,
    ]);

    foreach ($bookings as $booking) {
        $customer->updateFromBooking($booking);
    }

    expect($customer->total_bookings)->toBe(3)
        ->and((float) $customer->total_spent)->toBe(90.00)
        ->and($customer->loyalty_points)->toBe(30);
});

// --- StaffMember.locations() junction table ---

it('assigns staff member to locations via junction table', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location1 = Location::factory()->create(['business_id' => $business->id]);
    $location2 = Location::factory()->create(['business_id' => $business->id]);
    $staff = StaffMember::factory()->create(['business_id' => $business->id]);

    $staff->locations()->attach([$location1->id, $location2->id]);

    expect($staff->locations)->toHaveCount(2)
        ->and($staff->locations->pluck('id')->toArray())
        ->toContain($location1->id, $location2->id);
});

it('scopes staff members who work at a specific location', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location1 = Location::factory()->create(['business_id' => $business->id]);
    $location2 = Location::factory()->create(['business_id' => $business->id]);

    $staffAtBoth = StaffMember::factory()->create(['business_id' => $business->id]);
    $staffAtBoth->locations()->attach([$location1->id, $location2->id]);

    $staffAtOne = StaffMember::factory()->create(['business_id' => $business->id]);
    $staffAtOne->locations()->attach([$location1->id]);

    $staffAtOther = StaffMember::factory()->create(['business_id' => $business->id]);
    $staffAtOther->locations()->attach([$location2->id]);

    $atLocation1 = StaffMember::worksAtLocation($location1)->get();
    $atLocation2 = StaffMember::worksAtLocation($location2)->get();

    expect($atLocation1)->toHaveCount(2)
        ->and($atLocation1->pluck('id')->toArray())
        ->toContain($staffAtBoth->id, $staffAtOne->id)
        ->and($atLocation2)->toHaveCount(2)
        ->and($atLocation2->pluck('id')->toArray())
        ->toContain($staffAtBoth->id, $staffAtOther->id);
});

it('lists staff members from location side', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $staff1 = StaffMember::factory()->create(['business_id' => $business->id]);
    $staff2 = StaffMember::factory()->create(['business_id' => $business->id]);

    $location->staffMembers()->attach([$staff1->id, $staff2->id]);

    expect($location->staffMembers)->toHaveCount(2);
});

it('detaches staff from location without deleting either record', function () {
    $business = Business::factory()->solo()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $staff = StaffMember::factory()->create(['business_id' => $business->id]);

    $staff->locations()->attach($location->id);
    expect($staff->locations)->toHaveCount(1);

    $staff->locations()->detach($location->id);
    $staff->refresh();

    expect($staff->locations)->toHaveCount(0)
        ->and(StaffMember::find($staff->id))->not->toBeNull()
        ->and(Location::find($location->id))->not->toBeNull();
});
