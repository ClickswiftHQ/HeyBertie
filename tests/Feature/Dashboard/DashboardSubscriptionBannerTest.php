<?php

use App\Models\Business;
use App\Models\User;

test('trial business shares subscription props correctly', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->solo()->completed()->create([
        'owner_user_id' => $user->id,
        'trial_ends_at' => now()->addDays(10),
    ]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentBusiness.has_active_subscription', true)
            ->where('currentBusiness.on_trial', true)
            ->where('currentBusiness.trial_days_remaining', 10)
        );
});

test('trial with less than a day remaining shows 1 day', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->solo()->completed()->create([
        'owner_user_id' => $user->id,
        'trial_ends_at' => now()->addHours(6),
    ]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentBusiness.on_trial', true)
            ->where('currentBusiness.trial_days_remaining', 1)
        );
});

test('expired trial business shares correct subscription props', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->solo()->completed()->trialExpired()->create([
        'owner_user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentBusiness.has_active_subscription', false)
            ->where('currentBusiness.on_trial', false)
            ->where('currentBusiness.trial_days_remaining', null)
        );
});

test('free tier business shares correct subscription props', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->completed()->create([
        'owner_user_id' => $user->id,
        'trial_ends_at' => null,
    ]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentBusiness.subscription_tier', 'free')
            ->where('currentBusiness.has_active_subscription', false)
            ->where('currentBusiness.on_trial', false)
            ->where('currentBusiness.trial_days_remaining', null)
        );
});
