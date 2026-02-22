<?php

use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;

test('dog groomer landing page returns 200', function () {
    $this->get('/for-dog-groomers')
        ->assertSuccessful()
        ->assertViewIs('marketing.for-dog-groomers');
});

test('dog groomer landing page has SEO meta description', function () {
    $this->get('/for-dog-groomers')
        ->assertSuccessful()
        ->assertSee('<meta name="description"', false);
});

test('dog groomer landing page has canonical URL', function () {
    $this->get('/for-dog-groomers')
        ->assertSuccessful()
        ->assertSee('<link rel="canonical"', false);
});

test('dog groomer landing page CTA links to join route', function () {
    $this->get('/for-dog-groomers')
        ->assertSuccessful()
        ->assertSee(route('join'));
});

test('homepage learn more links to dog groomer landing page', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee(route('marketing.for-dog-groomers'));
});

test('navigation for groomers links to dog groomer landing page', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('href="'.route('marketing.for-dog-groomers').'"', false);
});

test('for-dog-groomers is rejected as a business handle', function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Business']);

    $this->post(route('onboarding.store', 3), ['handle' => 'for-dog-groomers'])
        ->assertSessionHasErrors('handle');
});

test('for- prefix handles are rejected as business handles', function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Business']);

    $this->post(route('onboarding.store', 3), ['handle' => 'for-dog-walkers'])
        ->assertSessionHasErrors('handle');
});
