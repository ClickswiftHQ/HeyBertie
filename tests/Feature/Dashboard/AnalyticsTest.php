<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->user->id]);
    $this->location = Location::factory()->create(['business_id' => $this->business->id]);
    $this->service = Service::factory()->create(['business_id' => $this->business->id]);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
});

test('guests are redirected to login', function () {
    $this->get("/{$this->business->handle}/analytics")
        ->assertRedirect(route('login'));
});

test('unauthorized users get 403', function () {
    $stranger = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->get("/{$this->business->handle}/analytics")
        ->assertForbidden();
});

test('renders analytics with all expected props and default period', function () {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/analytics/index')
            ->where('period', '30')
            ->has('stats')
            ->has('revenueChart')
            ->has('bookingsChart')
            ->has('topServices')
            ->has('busiestDays')
        );
});

test('accepts valid period parameters', function (string $period) {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period={$period}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('period', $period)
        );
})->with(['7', '90', 'all']);

test('defaults to 30 for invalid period', function () {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period=invalid")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('period', '30')
        );
});

test('stats reflect period filter', function () {
    // Booking within last 7 days
    Booking::factory()->completed()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->subDays(3),
    ]);

    // Booking outside 7-day window but within 30 days
    Booking::factory()->completed()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->subDays(15),
    ]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period=7")
        ->assertInertia(fn ($page) => $page
            ->where('stats.totalBookings', 1)
        );

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period=30")
        ->assertInertia(fn ($page) => $page
            ->where('stats.totalBookings', 2)
        );
});

test('top services ordered by booking count', function () {
    $serviceA = Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Bath']);
    $serviceB = Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Full Groom']);

    // 1 booking for Bath
    Booking::factory()->completed()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $serviceA->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->subDays(5),
    ]);

    // 2 bookings for Full Groom
    Booking::factory()->completed()->count(2)->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $serviceB->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->subDays(5),
    ]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period=30")
        ->assertInertia(fn ($page) => $page
            ->has('topServices', 2)
            ->where('topServices.0.name', 'Full Groom')
            ->where('topServices.0.bookings_count', 2)
            ->where('topServices.1.name', 'Bath')
            ->where('topServices.1.bookings_count', 1)
        );
});

test('does not include cancelled bookings in stats', function () {
    Booking::factory()->completed()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->subDays(5),
    ]);

    Booking::factory()->cancelled()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->subDays(3),
    ]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period=30")
        ->assertInertia(fn ($page) => $page
            ->where('stats.totalBookings', 1)
        );
});

test('does not include other business data', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $otherLocation = Location::factory()->create(['business_id' => $otherBusiness->id]);
    $otherService = Service::factory()->create(['business_id' => $otherBusiness->id]);
    $otherCustomer = Customer::factory()->create(['business_id' => $otherBusiness->id]);

    Booking::factory()->completed()->create([
        'business_id' => $otherBusiness->id,
        'location_id' => $otherLocation->id,
        'service_id' => $otherService->id,
        'customer_id' => $otherCustomer->id,
        'appointment_datetime' => now()->subDays(5),
    ]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/analytics?period=30")
        ->assertInertia(fn ($page) => $page
            ->where('stats.totalBookings', 0)
        );
});
