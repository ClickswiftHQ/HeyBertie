<?php

use App\Models\Business;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;

beforeEach(function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'trial_days' => 0, 'sort_order' => 1]);
    $this->soloTier = SubscriptionTier::firstOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 1999, 'sms_quota' => 30, 'trial_days' => 14, 'stripe_price_id' => 'price_solo_monthly', 'sort_order' => 2]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2]);

    $this->owner = User::factory()->create();
    $this->business = Business::factory()->solo()->completed()->verified()->create([
        'owner_user_id' => $this->owner->id,
    ]);
});

it('requires authentication for checkout', function () {
    $this->get(route('subscription.checkout', $this->business->handle))
        ->assertRedirect('/login');
});

it('requires authentication for billing portal', function () {
    $this->get(route('subscription.billing', $this->business->handle))
        ->assertRedirect('/login');
});

it('returns success flash on success route', function () {
    $this->actingAs($this->owner)
        ->get(route('subscription.success', $this->business->handle))
        ->assertRedirectToRoute('business.dashboard', $this->business->handle)
        ->assertSessionHas('success', 'Your subscription is now active!');
});

it('returns info flash on cancel route', function () {
    $this->actingAs($this->owner)
        ->get(route('subscription.cancelled', $this->business->handle))
        ->assertRedirectToRoute('business.dashboard', $this->business->handle)
        ->assertSessionHas('info', 'Checkout was cancelled. You can subscribe at any time.');
});

it('redirects to dashboard when stripe_price_id is not configured', function () {
    $this->soloTier->update(['stripe_price_id' => null]);

    $this->actingAs($this->owner)
        ->get(route('subscription.checkout', $this->business->handle))
        ->assertRedirectToRoute('business.dashboard', $this->business->handle)
        ->assertSessionHas('error');
});

it('forbids non-owners from accessing checkout', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('subscription.checkout', $this->business->handle))
        ->assertForbidden();
});
