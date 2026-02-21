<?php

use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;

beforeEach(function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);

    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($this->user);
});

test('step 1 requires business_type', function () {
    $this->post(route('onboarding.store', 1), [])
        ->assertSessionHasErrors('business_type');
});

test('step 1 rejects invalid business_type', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'invalid'])
        ->assertSessionHasErrors('business_type');
});

test('step 1 accepts valid business types', function (string $type) {
    $this->post(route('onboarding.store', 1), ['business_type' => $type])
        ->assertSessionDoesntHaveErrors('business_type');
})->with(['salon', 'mobile', 'home_based', 'hybrid']);

test('step 2 requires business name', function () {
    // Complete step 1 first
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);

    $this->post(route('onboarding.store', 2), [])
        ->assertSessionHasErrors('name');
});

test('step 2 validates phone format', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);

    $this->post(route('onboarding.store', 2), [
        'name' => 'Test',
        'phone' => 'not-a-phone',
    ])->assertSessionHasErrors('phone');
});

test('step 4 requires address fields', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-step4']);

    $this->post(route('onboarding.store', 4), [])
        ->assertSessionHasErrors(['address_line_1', 'town', 'city', 'postcode']);
});

test('step 4 validates UK postcode format', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-postcode']);

    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '1 Street',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => '12345',
    ])->assertSessionHasErrors('postcode');
});

test('step 5 requires at least one service', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-svc']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '1 Street',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW1A 1AA',
    ]);

    $this->post(route('onboarding.store', 5), ['services' => []])
        ->assertSessionHasErrors('services');
});

test('step 7 requires tier selection', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-plan']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '1 Street',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW1A 1AA',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 30, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => \Illuminate\Http\UploadedFile::fake()->image('id.jpg')->size(500),
    ]);

    $this->post(route('onboarding.store', 7), [])
        ->assertSessionHasErrors('tier');
});

test('step 7 rejects invalid tier', function () {
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-bad-tier']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '1 Street',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW1A 1AA',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 30, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => \Illuminate\Http\UploadedFile::fake()->image('id.jpg')->size(500),
    ]);

    $this->post(route('onboarding.store', 7), ['tier' => 'enterprise'])
        ->assertSessionHasErrors('tier');
});
