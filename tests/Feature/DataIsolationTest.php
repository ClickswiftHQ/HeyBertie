<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\BusinessPet;
use App\Models\BusinessRole;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;

it('isolates customers between businesses', function () {
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    Customer::factory()->count(5)->create(['business_id' => $business1->id]);
    Customer::factory()->count(3)->create(['business_id' => $business2->id]);

    expect($business1->customers()->count())->toBe(5)
        ->and($business2->customers()->count())->toBe(3);
});

it('isolates bookings between businesses', function () {
    $business1 = Business::factory()->solo()->create();
    $business2 = Business::factory()->solo()->create();

    $location1 = Location::factory()->create(['business_id' => $business1->id]);
    $location2 = Location::factory()->create(['business_id' => $business2->id]);

    $service1 = Service::factory()->create(['business_id' => $business1->id]);
    $service2 = Service::factory()->create(['business_id' => $business2->id]);

    $customer1 = Customer::factory()->create(['business_id' => $business1->id]);
    $customer2 = Customer::factory()->create(['business_id' => $business2->id]);

    Booking::factory()->count(4)->create([
        'business_id' => $business1->id,
        'location_id' => $location1->id,
        'service_id' => $service1->id,
        'customer_id' => $customer1->id,
    ]);

    Booking::factory()->count(2)->create([
        'business_id' => $business2->id,
        'location_id' => $location2->id,
        'service_id' => $service2->id,
        'customer_id' => $customer2->id,
    ]);

    expect($business1->bookings()->count())->toBe(4)
        ->and($business2->bookings()->count())->toBe(2);
});

it('isolates services between businesses', function () {
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    Service::factory()->count(3)->create(['business_id' => $business1->id]);
    Service::factory()->count(5)->create(['business_id' => $business2->id]);

    expect($business1->services()->count())->toBe(3)
        ->and($business2->services()->count())->toBe(5);
});

it('isolates locations between businesses', function () {
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    Location::factory()->count(2)->create(['business_id' => $business1->id]);
    Location::factory()->create(['business_id' => $business2->id]);

    expect($business1->locations()->count())->toBe(2)
        ->and($business2->locations()->count())->toBe(1);
});

it('prevents user without access from seeing business data', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $business = Business::factory()->create(['owner_user_id' => $owner->id]);

    expect($stranger->hasAccessToBusiness($business))->toBeFalse()
        ->and($owner->hasAccessToBusiness($business))->toBeTrue();
});

it('allows staff to access business', function () {
    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $business = Business::factory()->create(['owner_user_id' => $owner->id]);
    $staffRole = BusinessRole::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'sort_order' => 3]);

    $business->users()->attach($staff->id, ['business_role_id' => $staffRole->id, 'is_active' => true]);

    expect($staff->hasAccessToBusiness($business))->toBeTrue();
});

it('user can own multiple businesses', function () {
    $owner = User::factory()->create();

    Business::factory()->create(['owner_user_id' => $owner->id]);
    Business::factory()->create(['owner_user_id' => $owner->id]);

    expect($owner->ownedBusinesses()->count())->toBe(2);
});

it('pets belong to users not customers', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    Customer::factory()->create(['business_id' => $business->id, 'user_id' => $user->id]);

    Pet::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->pets)->toHaveCount(2);
});

it('isolates business-pet notes between businesses', function () {
    $user = User::factory()->create();
    $pet = Pet::factory()->create(['user_id' => $user->id]);
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    BusinessPet::factory()->create([
        'business_id' => $business1->id,
        'pet_id' => $pet->id,
        'notes' => 'Groomer 1 private notes',
        'difficulty_rating' => 2,
    ]);

    BusinessPet::factory()->create([
        'business_id' => $business2->id,
        'pet_id' => $pet->id,
        'notes' => 'Groomer 2 private notes',
        'difficulty_rating' => 4,
    ]);

    $business1Notes = BusinessPet::where('business_id', $business1->id)->where('pet_id', $pet->id)->first();
    $business2Notes = BusinessPet::where('business_id', $business2->id)->where('pet_id', $pet->id)->first();

    expect($business1Notes->notes)->toBe('Groomer 1 private notes')
        ->and($business1Notes->difficulty_rating)->toBe(2)
        ->and($business2Notes->notes)->toBe('Groomer 2 private notes')
        ->and($business2Notes->difficulty_rating)->toBe(4);
});

it('scopes business pets correctly via relationship', function () {
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    $pet1 = Pet::factory()->create();
    $pet2 = Pet::factory()->create();
    $pet3 = Pet::factory()->create();

    BusinessPet::factory()->create(['business_id' => $business1->id, 'pet_id' => $pet1->id]);
    BusinessPet::factory()->create(['business_id' => $business1->id, 'pet_id' => $pet2->id]);
    BusinessPet::factory()->create(['business_id' => $business2->id, 'pet_id' => $pet3->id]);

    expect($business1->pets)->toHaveCount(2)
        ->and($business2->pets)->toHaveCount(1);
});
