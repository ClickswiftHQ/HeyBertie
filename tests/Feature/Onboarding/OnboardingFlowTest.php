<?php

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Location;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionTier::firstOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 2900, 'sms_quota' => 30, 'sort_order' => 2]);
    SubscriptionTier::firstOrCreate(['slug' => 'salon'], ['name' => 'Salon', 'monthly_price_pence' => 7900, 'staff_limit' => 5, 'location_limit' => 3, 'sms_quota' => 100, 'sort_order' => 3]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2]);
    BusinessRole::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner', 'sort_order' => 1]);
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('onboarding.index'))
        ->assertRedirect(route('login'));
});

test('new user starts at step 1', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('onboarding.index'))
        ->assertRedirect(route('onboarding.step', 1));
});

test('can save step 1 and progress to step 2', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->post(route('onboarding.store', 1), [
            'business_type' => 'salon',
        ])
        ->assertRedirect(route('onboarding.step', 2));

    $business = Business::where('owner_user_id', $user->id)->first();
    expect($business->onboarding['business_type'])->toBe('salon')
        ->and($business->onboarding['completed_steps'])->toContain(1);
});

test('cannot skip to step 3 without completing step 2', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    // Complete step 1
    $this->actingAs($user)
        ->post(route('onboarding.store', 1), ['business_type' => 'salon']);

    // Try to access step 3 directly
    $this->actingAs($user)
        ->get(route('onboarding.step', 3))
        ->assertRedirect(route('onboarding.step', 2));
});

test('can navigate back to previous steps', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    // Complete step 1
    $this->actingAs($user)
        ->post(route('onboarding.store', 1), ['business_type' => 'salon']);

    // Complete step 2
    $this->actingAs($user)
        ->post(route('onboarding.store', 2), ['name' => 'Test Grooming']);

    // Navigate back to step 1
    $this->actingAs($user)
        ->get(route('onboarding.step', 1))
        ->assertOk();
});

test('returning user resumes from last step', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    // Complete steps 1 and 2
    $this->actingAs($user)
        ->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->actingAs($user)
        ->post(route('onboarding.store', 2), ['name' => 'Test Grooming']);

    // Visiting index redirects to current step (3)
    $this->actingAs($user)
        ->get(route('onboarding.index'))
        ->assertRedirect(route('onboarding.step', 3));
});

test('complete flow creates business with all related records', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email_verified_at' => now()]);

    // Step 1: Business Type
    $this->actingAs($user)
        ->post(route('onboarding.store', 1), ['business_type' => 'salon']);

    // Step 2: Business Details
    $this->actingAs($user)
        ->post(route('onboarding.store', 2), ['name' => 'Muddy Paws Grooming']);

    // Step 3: Handle
    $this->actingAs($user)
        ->post(route('onboarding.store', 3), ['handle' => 'muddy-paws']);

    // Step 4: Location
    $this->actingAs($user)
        ->post(route('onboarding.store', 4), [
            'address_line_1' => '123 High Street',
            'town' => 'Fulham',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

    // Step 5: Services
    $this->actingAs($user)
        ->post(route('onboarding.store', 5), [
            'services' => [
                [
                    'name' => 'Full Groom',
                    'description' => '',
                    'duration_minutes' => 90,
                    'price' => 45.00,
                    'price_type' => 'fixed',
                ],
            ],
        ]);

    // Step 6: Verification
    $this->actingAs($user)
        ->post(route('onboarding.store', 6), [
            'photo_id' => UploadedFile::fake()->image('passport.jpg', 400, 300)->size(1000),
        ]);

    // Step 7: Plan
    $this->actingAs($user)
        ->post(route('onboarding.store', 7), ['tier' => 'solo']);

    // Review page should be accessible
    $this->actingAs($user)
        ->get(route('onboarding.review'))
        ->assertOk();

    // Submit
    $this->actingAs($user)
        ->post(route('onboarding.submit'));

    $business = Business::where('owner_user_id', $user->id)->first();

    // Redirects to business-specific dashboard after onboarding completion
    $this->assertNotNull($business);
    $this->get(route('business.dashboard', $business->handle))->assertSuccessful();

    expect($business->onboarding_completed)->toBeTrue()
        ->and($business->name)->toBe('Muddy Paws Grooming')
        ->and($business->handle)->toBe('muddy-paws')
        ->and($business->is_active)->toBeTrue()
        ->and($business->verification_status)->toBe('pending')
        ->and($business->locations)->toHaveCount(1)
        ->and($business->services)->toHaveCount(1)
        ->and($business->verificationDocuments)->toHaveCount(1);
});

test('user role is updated to pro after completion', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email_verified_at' => now(), 'role' => 'customer']);

    $this->actingAs($user);

    // Run through all steps
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Business']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-biz']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '1 Street',
        'town' => 'Whitechapel',
        'city' => 'London',
        'postcode' => 'E1 6AN',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 30, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('id.jpg')->size(500),
    ]);
    $this->post(route('onboarding.store', 7), ['tier' => 'free']);
    $this->post(route('onboarding.submit'));

    expect($user->fresh()->role)->toBe('pro');
});

test('completed onboarding redirects to dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->create([
        'owner_user_id' => $user->id,
        'onboarding_completed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('onboarding.index'))
        ->assertRedirect(route('dashboard'));
});
