<?php

use App\Models\Business;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;

beforeEach(function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);

    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($this->user);

    // Complete step 1 and 2 to access step 3
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Business']);
});

test('valid handle is accepted', function () {
    $this->post(route('onboarding.store', 3), ['handle' => 'valid-handle'])
        ->assertSessionDoesntHaveErrors('handle')
        ->assertRedirect(route('onboarding.step', 4));
});

test('reserved word is rejected', function () {
    $this->post(route('onboarding.store', 3), ['handle' => 'admin'])
        ->assertSessionHasErrors('handle');
});

test('duplicate handle is rejected', function () {
    Business::factory()->create(['handle' => 'taken-handle']);

    $this->post(route('onboarding.store', 3), ['handle' => 'taken-handle'])
        ->assertSessionHasErrors('handle');
});

test('handle format validation - too short', function () {
    $this->post(route('onboarding.store', 3), ['handle' => 'ab'])
        ->assertSessionHasErrors('handle');
});

test('handle format validation - uppercase rejected', function () {
    $this->post(route('onboarding.store', 3), ['handle' => 'Invalid-Handle'])
        ->assertSessionHasErrors('handle');
});

test('handle availability check returns correct response for available handle', function () {
    $this->postJson(route('onboarding.check-handle'), ['handle' => 'available-handle'])
        ->assertOk()
        ->assertJson(['available' => true]);
});

test('handle availability check returns suggestions when taken', function () {
    Business::factory()->create(['handle' => 'taken-handle']);

    $this->postJson(route('onboarding.check-handle'), ['handle' => 'taken-handle'])
        ->assertOk()
        ->assertJson(['available' => false])
        ->assertJsonStructure(['suggestions']);
});

test('handle availability check returns false for reserved words', function () {
    $this->postJson(route('onboarding.check-handle'), ['handle' => 'admin'])
        ->assertOk()
        ->assertJson(['available' => false]);
});
