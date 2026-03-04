<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Models\VerificationDocument;

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
});

// --- Business List ---

test('non-super user cannot access business list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/businesses')
        ->assertForbidden();
});

test('admin can view business list', function () {
    Business::factory()->completed()->create();
    Business::factory()->solo()->completed()->verified()->create();

    $this->actingAs($this->admin)
        ->get('/admin/businesses')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/businesses/index')
            ->has('businesses.data', 2)
            ->has('tiers')
            ->has('filters')
        );
});

test('admin can search businesses by name', function () {
    Business::factory()->completed()->create(['name' => 'Alpha Grooming']);
    Business::factory()->completed()->create(['name' => 'Beta Pets']);

    $this->actingAs($this->admin)
        ->get('/admin/businesses?search=Alpha')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('businesses.data', 1));
});

test('admin can filter businesses by verification status', function () {
    Business::factory()->completed()->verified()->create();
    Business::factory()->completed()->create(); // pending

    $this->actingAs($this->admin)
        ->get('/admin/businesses?verification=pending')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('businesses.data', 1));
});

test('admin can filter businesses by tier', function () {
    Business::factory()->solo()->completed()->create();
    Business::factory()->completed()->create(); // free

    $this->actingAs($this->admin)
        ->get('/admin/businesses?tier=solo')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('businesses.data', 1));
});

// --- Business Detail ---

test('admin can view business detail', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    Location::factory()->create(['business_id' => $business->id]);

    $this->actingAs($this->admin)
        ->get("/admin/businesses/{$business->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/businesses/show')
            ->has('business')
            ->has('recentBookings')
            ->has('stats')
            ->has('timeline')
            ->has('tiers')
            ->has('statuses')
        );
});

// --- Verify ---

test('admin can approve a pending business', function () {
    $business = Business::factory()->completed()->create([
        'verification_status' => 'pending',
    ]);
    VerificationDocument::factory()->create([
        'business_id' => $business->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/verify", [
            'decision' => 'approved',
            'notes' => 'All good',
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->verification_status)->toBe('verified')
        ->and($business->verified_at)->not->toBeNull()
        ->and($business->verification_notes)->toBe('All good');

    expect($business->verificationDocuments->first()->status)->toBe('approved');
});

test('admin can reject a pending business', function () {
    $business = Business::factory()->completed()->create([
        'verification_status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/verify", [
            'decision' => 'rejected',
            'notes' => 'Missing documents',
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->verification_status)->toBe('rejected')
        ->and($business->verified_at)->toBeNull();
});

test('verify requires valid decision', function () {
    $business = Business::factory()->completed()->create();

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/verify", [
            'decision' => 'invalid',
        ])
        ->assertSessionHasErrors('decision');
});

// --- Suspend ---

test('admin can suspend an active business', function () {
    $business = Business::factory()->completed()->verified()->create();

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/suspend", [
            'action' => 'suspend',
            'reason' => 'Fraudulent activity',
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->is_active)->toBeFalse();
});

test('admin can reactivate a suspended business', function () {
    $business = Business::factory()->completed()->suspended()->create();

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/suspend", [
            'action' => 'reactivate',
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->is_active)->toBeTrue();
});

// --- Update Subscription ---

test('admin can change subscription tier', function () {
    $business = Business::factory()->completed()->create();
    $soloTier = SubscriptionTier::firstOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 1999, 'sort_order' => 2]);
    $activeStatus = SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2]);

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/subscription", [
            'subscription_tier_id' => $soloTier->id,
            'subscription_status_id' => $activeStatus->id,
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->subscription_tier_id)->toBe($soloTier->id)
        ->and($business->subscription_status_id)->toBe($activeStatus->id);
});

// --- Update Trial ---

test('admin can extend trial period', function () {
    $business = Business::factory()->completed()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/trial", [
            'trial_ends_at' => now()->addDays(30)->toDateString(),
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->trial_ends_at)->not->toBeNull()
        ->and($business->trial_ends_at->isFuture())->toBeTrue();
});

test('admin can revoke trial by clearing date', function () {
    $business = Business::factory()->completed()->create([
        'trial_ends_at' => now()->addDays(10),
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/trial", [
            'trial_ends_at' => null,
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->trial_ends_at)->toBeNull();
});

// --- Update Handle ---

test('admin can change business handle', function () {
    $business = Business::factory()->completed()->create(['handle' => 'old-handle']);

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/handle", [
            'handle' => 'new-handle',
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->handle)->toBe('new-handle');

    // HandleChange record created for redirect
    expect($business->handleChanges()->count())->toBe(1);
    $change = $business->handleChanges()->first();
    expect($change->old_handle)->toBe('old-handle')
        ->and($change->new_handle)->toBe('new-handle')
        ->and($change->changed_by_user_id)->toBe($this->admin->id);
});

test('admin handle change rejects invalid handle', function () {
    $business = Business::factory()->completed()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/handle", [
            'handle' => 'ab', // too short
        ])
        ->assertSessionHasErrors('handle');
});

// --- Update Settings ---

test('admin can update business settings', function () {
    $business = Business::factory()->completed()->create([
        'settings' => ['auto_confirm_bookings' => true],
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/settings", [
            'auto_confirm_bookings' => false,
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->settings['auto_confirm_bookings'])->toBeFalse();
});

test('admin settings update merges without overwriting other keys', function () {
    $business = Business::factory()->completed()->create([
        'settings' => ['auto_confirm_bookings' => true, 'deposits_enabled' => true],
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/businesses/{$business->id}/settings", [
            'auto_confirm_bookings' => false,
        ])
        ->assertRedirect();

    $business->refresh();
    expect($business->settings['auto_confirm_bookings'])->toBeFalse()
        ->and($business->settings['deposits_enabled'])->toBeTrue();
});

// --- Cancel Booking ---

test('admin can cancel a booking', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    $booking = Booking::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'appointment_datetime' => now()->addDays(3),
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/bookings/{$booking->id}/cancel", [
            'cancellation_reason' => 'Customer requested via phone',
        ])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled')
        ->and($booking->cancellation_reason)->toBe('Customer requested via phone')
        ->and($booking->cancelled_by_user_id)->toBe($this->admin->id);
});

test('admin cannot cancel an already completed booking', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = Customer::factory()->create(['business_id' => $business->id]);
    $booking = Booking::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'completed',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/businesses/{$business->id}/bookings/{$booking->id}/cancel")
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('completed');
});

// --- Activity Timeline ---

test('business detail includes activity timeline events', function () {
    $business = Business::factory()->solo()->completed()->verified()->create();
    Location::factory()->create(['business_id' => $business->id]);

    $this->actingAs($this->admin)
        ->get("/admin/businesses/{$business->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('timeline')
            ->where('timeline', fn ($timeline) => collect($timeline)->contains('type', 'business_created')
                && collect($timeline)->contains('type', 'business_verified')
            )
        );
});
