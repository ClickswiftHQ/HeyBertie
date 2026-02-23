<?php

/**
 * Flow 2: Business Owner Journey E2E Tests
 *
 * Covers: join page, registration, email verification, 7-step onboarding,
 * step navigation, returning user, dashboard access, public visibility.
 */

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Location;
use App\Models\Service;
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

// ─── 2.1 Join Page ──────────────────────────────────────────────────

test('join page loads with business registration form', function () {
    $this->get(route('join'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('auth/register')
            ->where('intent', 'business')
        );
});

test('for-dog-groomers marketing page loads', function () {
    $this->get(route('marketing.for-dog-groomers'))
        ->assertSuccessful()
        ->assertViewIs('marketing.for-dog-groomers');
});

// ─── 2.2 Registration ──────────────────────────────────────────────

test('business registration via join sets session intent', function () {
    $this->get(route('join'));

    expect(session('registration_intent'))->toBe('business');
});

test('registration creates user and authenticates', function () {
    $this->get(route('join'));

    $this->post(route('register.store'), [
        'name' => 'Business Owner',
        'email' => 'testbiz@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    $this->assertAuthenticated();
});

test('business registration redirects to register complete', function () {
    $this->get(route('join'));

    $response = $this->post(route('register.store'), [
        'name' => 'Business Owner',
        'email' => 'testbiz2@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    $response->assertRedirect(route('register.complete'));
});

test('normal registration redirects to register complete', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Normal User',
        'email' => 'normal@example.com',
        'password' => 'password123456',
        'password_confirmation' => 'password123456',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('register.complete'));
});

// ─── 2.3 Email Verification ────────────────────────────────────────

test('unverified user is redirected to verification notice', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertRedirect(route('verification.notice'));
});

// ─── 2.4 Onboarding Flow (7 Steps) ────────────────────────────────

test('new user starts onboarding at step 1', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('onboarding.index'))
        ->assertRedirect(route('onboarding.step', 1));
});

test('step 1 business type can be saved', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->post(route('onboarding.store', 1), ['business_type' => 'salon'])
        ->assertRedirect(route('onboarding.step', 2));

    $business = Business::where('owner_user_id', $user->id)->first();
    expect($business->onboarding['business_type'])->toBe('salon');
});

test('step 2 business details can be saved', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);

    $this->post(route('onboarding.store', 2), ['name' => 'Test Grooming Studio'])
        ->assertRedirect(route('onboarding.step', 3));

    $business = Business::where('owner_user_id', $user->id)->first();
    expect($business->name)->toBe('Test Grooming Studio');
});

test('step 3 handle can be saved', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Studio']);

    $this->post(route('onboarding.store', 3), ['handle' => 'test-studio'])
        ->assertRedirect(route('onboarding.step', 4));

    $business = Business::where('owner_user_id', $user->id)->first();
    expect($business->handle)->toBe('test-studio');
});

test('step 4 location can be saved', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Studio']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-studio-4']);

    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '45 Lillie Road',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW6 1UD',
    ])->assertRedirect(route('onboarding.step', 5));
});

test('step 5 services can be saved', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Studio']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-studio-5']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '45 Lillie Road',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW6 1UD',
    ]);

    $this->post(route('onboarding.store', 5), [
        'services' => [
            [
                'name' => 'Full Groom',
                'description' => '',
                'duration_minutes' => 90,
                'price' => 45.00,
                'price_type' => 'fixed',
            ],
        ],
    ])->assertRedirect(route('onboarding.step', 6));
});

test('step 6 verification document can be uploaded', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Studio']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-studio-6']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '45 Lillie Road',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW6 1UD',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 30, 'price_type' => 'fixed']],
    ]);

    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('passport.jpg', 400, 300)->size(1000),
    ])->assertRedirect(route('onboarding.step', 7));
});

test('step 7 plan selection can be saved', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Studio']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-studio-7']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '45 Lillie Road',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW6 1UD',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 30, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('id.jpg')->size(500),
    ]);

    $this->post(route('onboarding.store', 7), ['tier' => 'solo'])
        ->assertRedirect(route('onboarding.review'));
});

test('review page shows all entered data', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Grooming Studio']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-studio-rev']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '45 Lillie Road',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW6 1UD',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Full Groom', 'description' => '', 'duration_minutes' => 90, 'price' => 45, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('id.jpg')->size(500),
    ]);
    $this->post(route('onboarding.store', 7), ['tier' => 'solo']);

    $this->get(route('onboarding.review'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/shared/review')
            ->where('business.name', 'Test Grooming Studio')
            ->where('business.handle', 'test-studio-rev')
            ->has('services', 1)
            ->has('plan')
        );
});

test('submit finalises onboarding and redirects to dashboard', function () {
    Storage::fake('local');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Muddy Paws Grooming']);
    $this->post(route('onboarding.store', 3), ['handle' => 'muddy-paws-e2e']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '123 High Street',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW1A 1AA',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Full Groom', 'description' => '', 'duration_minutes' => 90, 'price' => 45.00, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('passport.jpg', 400, 300)->size(1000),
    ]);
    $this->post(route('onboarding.store', 7), ['tier' => 'solo']);
    $this->post(route('onboarding.submit'));

    $business = Business::where('owner_user_id', $user->id)->first();

    expect($business->onboarding_completed)->toBeTrue()
        ->and($business->name)->toBe('Muddy Paws Grooming')
        ->and($business->handle)->toBe('muddy-paws-e2e')
        ->and($business->is_active)->toBeTrue()
        ->and($business->locations)->toHaveCount(1)
        ->and($business->services)->toHaveCount(1)
        ->and($business->verificationDocuments)->toHaveCount(1);
});

// ─── 2.5 Step Navigation ───────────────────────────────────────────

test('cannot skip to step 5 without completing step 4', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-skip']);

    $this->get(route('onboarding.step', 5))
        ->assertRedirect(route('onboarding.step', 4));
});

test('can navigate back to completed step and data is preserved', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Grooming']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-back']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '45 Lillie Road',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW6 1UD',
    ]);

    // Navigate back to step 2
    $this->get(route('onboarding.step', 2))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('business.name', 'Test Grooming')
        );
});

// ─── 2.6 Returning User ────────────────────────────────────────────

test('returning user resumes at last incomplete step', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Grooming']);

    // Simulate returning — visit /onboarding
    $this->get(route('onboarding.index'))
        ->assertRedirect(route('onboarding.step', 3));
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

// ─── 2.7 Dashboard ─────────────────────────────────────────────────

test('business owner can access dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/index')
            ->has('stats')
            ->has('upcomingBookings')
            ->has('recentActivity')
        );
});

test('stranger cannot access another business dashboard', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $stranger = User::factory()->create(['email_verified_at' => now()]);

    // Give the stranger a completed business so onboarding middleware passes
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $business = Business::factory()->completed()->create(['owner_user_id' => $owner->id]);

    $this->actingAs($stranger)
        ->get("/{$business->handle}/dashboard")
        ->assertForbidden();
});

test('guest is redirected to login from dashboard', function () {
    $business = Business::factory()->completed()->create();

    $this->get("/{$business->handle}/dashboard")
        ->assertRedirect(route('login'));
});

// ─── 2.8 Public Visibility ─────────────────────────────────────────

test('completed business listing is publicly visible', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get("/{$business->handle}/{$location->slug}")
        ->assertSuccessful()
        ->assertViewIs('listing.show')
        ->assertSee($business->name);
});

test('multi-location hub page is publicly visible', function () {
    $business = Business::factory()->completed()->create();
    Location::factory()->create(['business_id' => $business->id, 'name' => 'Location A']);
    Location::factory()->create(['business_id' => $business->id, 'name' => 'Location B', 'is_primary' => false]);

    $this->get("/{$business->handle}")
        ->assertSuccessful()
        ->assertViewIs('listing.hub')
        ->assertSee('Location A')
        ->assertSee('Location B');
});

test('business listing shows active services only', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id, 'location_id' => $location->id, 'name' => 'Active Groom', 'is_active' => true]);
    Service::factory()->create(['business_id' => $business->id, 'location_id' => $location->id, 'name' => 'Hidden Groom', 'is_active' => false]);

    $this->get("/{$business->handle}/{$location->slug}")
        ->assertSuccessful()
        ->assertSee('Active Groom')
        ->assertDontSee('Hidden Groom');
});
