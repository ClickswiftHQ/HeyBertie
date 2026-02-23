<?php

use App\Models\Business;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users with completed business are redirected to handle dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('business.dashboard', $business->handle));
});

test('authenticated users without completed business are redirected to my-bookings', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('customer.bookings.index'));
});
