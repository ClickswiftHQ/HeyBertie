<?php

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\SubscriptionTier;
use App\Models\User;

it('belongs to an owner', function () {
    $business = Business::factory()->create();

    expect($business->owner)->toBeInstanceOf(User::class);
});

it('has many locations', function () {
    $business = Business::factory()->create();
    Location::factory()->count(2)->create(['business_id' => $business->id]);

    expect($business->locations)->toHaveCount(2);
});

it('has many services', function () {
    $business = Business::factory()->create();
    Service::factory()->count(3)->create(['business_id' => $business->id]);

    expect($business->services)->toHaveCount(3);
});

it('has many customers', function () {
    $business = Business::factory()->create();
    Customer::factory()->count(5)->create(['business_id' => $business->id]);

    expect($business->customers)->toHaveCount(5);
});

it('has many staff members', function () {
    $business = Business::factory()->salon()->create();
    StaffMember::factory()->count(2)->create(['business_id' => $business->id]);

    expect($business->staffMembers)->toHaveCount(2);
});

it('has many-to-many relationship with users via pivot', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create();
    $staffRole = BusinessRole::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'sort_order' => 3]);

    $business->users()->attach($user->id, ['business_role_id' => $staffRole->id, 'is_active' => true]);

    expect($business->users)->toHaveCount(1)
        ->and($business->users->first()->pivot->business_role_id)->toBe($staffRole->id);
});

it('has subscription tier relationship', function () {
    $business = Business::factory()->salon()->create();

    expect($business->subscriptionTier)->toBeInstanceOf(SubscriptionTier::class)
        ->and($business->subscriptionTier->slug)->toBe('salon');
});

it('scopes to verified businesses', function () {
    Business::factory()->verified()->count(2)->create();
    Business::factory()->create(['verification_status' => 'pending']);

    expect(Business::verified()->count())->toBe(2);
});

it('scopes to active businesses', function () {
    Business::factory()->count(3)->create(['is_active' => true]);
    Business::factory()->create(['is_active' => false]);

    expect(Business::active()->count())->toBe(3);
});

it('scopes to specific tier', function () {
    Business::factory()->solo()->create();
    Business::factory()->salon()->create();
    Business::factory()->create();

    expect(Business::tier('salon')->count())->toBe(1)
        ->and(Business::tier('solo')->count())->toBe(1);
});

it('detects if user is owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $business = Business::factory()->create(['owner_user_id' => $owner->id]);

    expect($business->isOwner($owner))->toBeTrue()
        ->and($business->isOwner($other))->toBeFalse();
});

it('detects if user has access', function () {
    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $stranger = User::factory()->create();
    $staffRole = BusinessRole::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'sort_order' => 3]);

    $business = Business::factory()->create(['owner_user_id' => $owner->id]);
    $business->users()->attach($staff->id, ['business_role_id' => $staffRole->id, 'is_active' => true]);

    expect($business->canAccess($owner))->toBeTrue()
        ->and($business->canAccess($staff))->toBeTrue()
        ->and($business->canAccess($stranger))->toBeFalse();
});

it('enforces staff limit for salon tier', function () {
    $business = Business::factory()->salon()->create();

    expect($business->canAddStaff())->toBeTrue();

    StaffMember::factory()->count(5)->create(['business_id' => $business->id, 'is_active' => true]);

    $business->refresh();

    expect($business->canAddStaff())->toBeFalse();
});

it('prevents staff for non-salon tiers', function () {
    $solo = Business::factory()->solo()->create();
    $free = Business::factory()->create();

    expect($solo->canAddStaff())->toBeFalse()
        ->and($free->canAddStaff())->toBeFalse();
});

it('calculates average rating', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create();

    Review::factory()->create(['business_id' => $business->id, 'user_id' => $user->id, 'rating' => 5, 'is_published' => true]);
    Review::factory()->create(['business_id' => $business->id, 'user_id' => User::factory(), 'rating' => 3, 'is_published' => true]);

    expect($business->getAverageRating())->toBe(4.0);
});

it('calculates rating breakdown', function () {
    $business = Business::factory()->create();

    Review::factory()->create(['business_id' => $business->id, 'user_id' => User::factory(), 'rating' => 5, 'is_published' => true]);
    Review::factory()->create(['business_id' => $business->id, 'user_id' => User::factory(), 'rating' => 5, 'is_published' => true]);
    Review::factory()->create(['business_id' => $business->id, 'user_id' => User::factory(), 'rating' => 4, 'is_published' => true]);

    $breakdown = $business->getRatingBreakdown();

    expect($breakdown[5])->toBe(2)
        ->and($breakdown[4])->toBe(1)
        ->and($breakdown[1])->toBe(0);
});

it('uses soft deletes', function () {
    $business = Business::factory()->create();
    $business->delete();

    expect(Business::count())->toBe(0)
        ->and(Business::withTrashed()->count())->toBe(1);
});
