<?php

use App\Models\Business;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;

beforeEach(function () {
    $this->freeTier = SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'trial_days' => 0, 'sort_order' => 1]);
    $this->soloTier = SubscriptionTier::firstOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 1999, 'sms_quota' => 30, 'trial_days' => 14, 'sort_order' => 2]);
    $this->trialStatus = SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);
    $this->activeStatus = SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2]);
    $this->cancelledStatus = SubscriptionStatus::firstOrCreate(['slug' => 'cancelled'], ['name' => 'Cancelled', 'sort_order' => 4]);
});

it('downgrades expired trials to free tier', function () {
    $business = Business::factory()->solo()->completed()->verified()->trialExpired()->create();

    $this->artisan('subscriptions:expire-trials')
        ->expectsOutputToContain('Expired 1 trial(s)')
        ->assertSuccessful();

    $business->refresh();

    expect($business->subscription_tier_id)->toBe($this->freeTier->id)
        ->and($business->subscription_status_id)->toBe($this->cancelledStatus->id);
});

it('does not downgrade active trials', function () {
    $business = Business::factory()->completed()->verified()->create([
        'subscription_tier_id' => $this->soloTier->id,
        'subscription_status_id' => $this->trialStatus->id,
        'trial_ends_at' => now()->addDays(7),
    ]);

    $this->artisan('subscriptions:expire-trials')
        ->expectsOutputToContain('Expired 0 trial(s)')
        ->assertSuccessful();

    $business->refresh();

    expect($business->subscription_tier_id)->toBe($this->soloTier->id)
        ->and($business->subscription_status_id)->toBe($this->trialStatus->id);
});

it('does not downgrade businesses with active Cashier subscriptions', function () {
    $business = Business::factory()->solo()->completed()->verified()->trialExpired()->create([
        'stripe_id' => 'cus_test_123',
    ]);

    // Create a Cashier subscription record
    $business->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_solo_monthly',
    ]);

    $this->artisan('subscriptions:expire-trials')
        ->expectsOutputToContain('Expired 0 trial(s)')
        ->assertSuccessful();

    $business->refresh();

    expect($business->subscription_tier_id)->toBe($this->soloTier->id);
});

it('handles multiple expired trials at once', function () {
    Business::factory()->solo()->completed()->verified()->trialExpired()->count(3)->create();

    $this->artisan('subscriptions:expire-trials')
        ->expectsOutputToContain('Expired 3 trial(s)')
        ->assertSuccessful();

    expect(Business::where('subscription_tier_id', $this->freeTier->id)->count())->toBe(3);
});
