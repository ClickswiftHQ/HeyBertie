<?php

/**
 * Flow 3: Cross-Journey Verification E2E Tests
 *
 * Covers: new business appears in search results after onboarding,
 * data isolation (inactive/draft excluded, published reviews only, active services only).
 */

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\GeocodeCache;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionTier::firstOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 2900, 'sms_quota' => 30, 'sort_order' => 2]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);
    BusinessRole::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner', 'sort_order' => 1]);
});

// ─── 3.1 New Business Appears in Search ─────────────────────────────

test('newly onboarded business appears in search results', function () {
    Storage::fake('local');

    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'New London Groomer']);
    $this->post(route('onboarding.store', 3), ['handle' => 'new-london-groomer']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '10 Oxford Street',
        'town' => 'Westminster',
        'city' => 'London',
        'postcode' => 'W1D 1BS',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Full Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 40, 'price_type' => 'fixed']],
    ]);
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('id.jpg')->size(500),
    ]);
    $this->post(route('onboarding.store', 7), ['tier' => 'solo']);
    $this->post(route('onboarding.submit'));

    $business = Business::where('owner_user_id', $user->id)->first();
    expect($business->onboarding_completed)->toBeTrue();

    // Manually set coordinates on the location so it appears in search
    $location = $business->locations()->first();
    $location->update(['latitude' => 51.5155, 'longitude' => -0.1419]);

    // Search near London — the new business should appear
    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertSee('New London Groomer');
});

// ─── 3.2 Data Isolation ─────────────────────────────────────────────

test('inactive businesses are excluded from search results', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $business = Business::factory()->completed()->create(['is_active' => false, 'name' => 'Inactive Groomer']);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertDontSee('Inactive Groomer');
});

test('draft businesses are excluded from search results', function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);

    $business = Business::factory()->create(['onboarding_completed' => false, 'name' => 'Draft Groomer']);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $this->get('/dog-grooming-in-london')
        ->assertSuccessful()
        ->assertDontSee('Draft Groomer');
});

test('inactive businesses are excluded from postcode search', function () {
    $mock = $this->mock(GeocodingService::class);
    $mock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $business = Business::factory()->completed()->create(['is_active' => false, 'name' => 'Hidden Business']);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.4823,
        'longitude' => -0.1953,
    ]);

    $this->get('/search?location=SW6+1UD')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 0);
});

test('draft businesses are excluded from postcode search', function () {
    $mock = $this->mock(GeocodingService::class);
    $mock->shouldReceive('geocode')
        ->with('SW6 1UD')
        ->andReturn(['latitude' => 51.4823, 'longitude' => -0.1953]);

    $business = Business::factory()->create(['onboarding_completed' => false, 'name' => 'Draft Business']);
    Location::factory()->create([
        'business_id' => $business->id,
        'latitude' => 51.4823,
        'longitude' => -0.1953,
    ]);

    $this->get('/search?location=SW6+1UD')
        ->assertSuccessful()
        ->assertViewHas('totalResults', 0);
});

test('only published reviews appear on listing pages', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Review::factory()->create(['business_id' => $business->id, 'review_text' => 'Great service!', 'is_published' => true]);
    Review::factory()->create(['business_id' => $business->id, 'review_text' => 'Hidden review text', 'is_published' => false]);

    $this->get("/{$business->handle}/{$location->slug}")
        ->assertSuccessful()
        ->assertSee('Great service!')
        ->assertDontSee('Hidden review text');
});

test('only active services appear on listing pages', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    Service::factory()->create(['business_id' => $business->id, 'location_id' => $location->id, 'name' => 'Visible Service', 'is_active' => true]);
    Service::factory()->create(['business_id' => $business->id, 'location_id' => $location->id, 'name' => 'Hidden Service', 'is_active' => false]);

    $this->get("/{$business->handle}/{$location->slug}")
        ->assertSuccessful()
        ->assertSee('Visible Service')
        ->assertDontSee('Hidden Service');
});

test('draft business listing returns 404 for public', function () {
    $business = Business::factory()->create([
        'onboarding_completed' => false,
        'name' => 'Draft Business',
    ]);
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get("/{$business->handle}/{$location->slug}")
        ->assertNotFound();
});

test('inactive business listing returns 404 for public', function () {
    $business = Business::factory()->completed()->create(['is_active' => false]);
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get("/{$business->handle}/{$location->slug}")
        ->assertNotFound();
});
