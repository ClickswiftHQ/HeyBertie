<?php

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\User;

test('currentBusiness is shared in inertia props on management routes', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentBusiness.id', $business->id)
            ->where('currentBusiness.name', $business->name)
            ->where('currentBusiness.handle', $business->handle)
            ->has('currentBusiness.subscription_tier')
        );
});

test('userBusinesses includes all accessible businesses', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business1 = Business::factory()->completed()->create(['owner_user_id' => $user->id]);
    Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/{$business1->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('userBusinesses', 2)
        );
});

test('userBusinesses includes staff businesses', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $ownBusiness = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $otherOwner = User::factory()->create(['email_verified_at' => now()]);
    $staffBusiness = Business::factory()->completed()->create(['owner_user_id' => $otherOwner->id]);
    $staffRole = BusinessRole::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'sort_order' => 3]);
    $staffBusiness->users()->attach($user->id, ['business_role_id' => $staffRole->id]);

    $this->actingAs($user)
        ->get("/{$ownBusiness->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('userBusinesses', 2)
        );
});

test('userBusinesses excludes inactive businesses', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $active = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    // Inactive business — owned by user but should be excluded from list
    Business::factory()->completed()->create(['owner_user_id' => $user->id, 'is_active' => false]);

    $this->actingAs($user)
        ->get("/{$active->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('userBusinesses', 1)
        );
});

test('currentBusiness is null on non-management routes', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentBusiness', null)
        );
});
