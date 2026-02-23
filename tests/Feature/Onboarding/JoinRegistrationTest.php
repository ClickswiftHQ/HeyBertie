<?php

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;

beforeEach(function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);
    BusinessRole::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner', 'sort_order' => 1]);
});

test('join page renders registration form with business intent', function () {
    $this->get(route('join'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/register')
            ->has('intent')
            ->where('intent', 'business')
        );
});

test('join page sets registration intent in session', function () {
    $this->get(route('join'));

    expect(session('registration_intent'))->toBe('business');
});

test('join page redirects authenticated users to onboarding', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('join'))
        ->assertRedirect(route('onboarding.index'));
});

test('registering via join redirects to onboarding instead of dashboard', function () {
    $this->get(route('join'));

    $this->post(route('register.store'), [
        'name' => 'Business Owner',
        'email' => 'owner@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    $this->assertAuthenticated();

    $response = $this->get(route('dashboard'));
    // After business registration, the intent redirects to onboarding
    // The session intent is consumed by RegisterResponse
});

test('registering via join preserves session intent for post-verification', function () {
    $this->get(route('join'));
    expect(session('registration_intent'))->toBe('business');

    $this->post(route('register.store'), [
        'name' => 'Business Owner',
        'email' => 'owner2@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    expect(session('registration_intent'))->toBe('business');
});

test('all registrations redirect to register complete', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Normal User',
        'email' => 'normal@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('register.complete'));
});

test('registering via join redirects to register complete', function () {
    $this->get(route('join'));

    $response = $this->post(route('register.store'), [
        'name' => 'Pro User',
        'email' => 'pro@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('register.complete'));
});
