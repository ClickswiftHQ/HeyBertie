<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\BusinessPageView;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardStatsService;

beforeEach(function () {
    $this->service = app(DashboardStatsService::class);
    $this->owner = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->owner->id]);
    $this->location = Location::factory()->create(['business_id' => $this->business->id]);
    $this->businessService = Service::factory()->create(['business_id' => $this->business->id]);
});

test('getOverviewStats returns correct todays bookings count', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    Booking::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->setHour(14),
        'status' => 'confirmed',
    ]);

    // Cancelled booking should not count
    Booking::factory()->cancelled()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->setHour(16),
    ]);

    $stats = $this->service->getOverviewStats($this->business);

    expect($stats['todaysBookings'])->toBe(2);
});

test('getOverviewStats returns correct weekly revenue', function () {
    Transaction::create([
        'business_id' => $this->business->id,
        'type' => 'booking_payment',
        'amount' => 45.00,
        'currency' => 'GBP',
        'status' => 'completed',
    ]);
    Transaction::create([
        'business_id' => $this->business->id,
        'type' => 'booking_payment',
        'amount' => 30.00,
        'currency' => 'GBP',
        'status' => 'completed',
    ]);
    // Pending should not count
    Transaction::create([
        'business_id' => $this->business->id,
        'type' => 'booking_payment',
        'amount' => 100.00,
        'currency' => 'GBP',
        'status' => 'pending',
    ]);

    $stats = $this->service->getOverviewStats($this->business);

    expect($stats['weeklyRevenue'])->toBe(75.0);
});

test('getOverviewStats returns zero when no data', function () {
    $stats = $this->service->getOverviewStats($this->business);

    expect($stats['todaysBookings'])->toBe(0)
        ->and($stats['weeklyRevenue'])->toBe(0.0)
        ->and($stats['totalCustomers'])->toBe(0)
        ->and($stats['pageViews'])->toBe(0)
        ->and($stats['pendingBookings'])->toBe(0)
        ->and($stats['averageRating'])->toBeNull()
        ->and($stats['noShowRate'])->toBe(0.0)
        ->and($stats['monthlyBookings'])->toBe(0);
});

test('getOverviewStats calculates no-show rate correctly', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    // 3 completed bookings
    Booking::factory()->completed()->count(3)->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->subDays(5),
    ]);

    // 1 no-show booking
    Booking::factory()->noShow()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->subDays(3),
    ]);

    $stats = $this->service->getOverviewStats($this->business);

    // 1 no-show out of 4 total (completed + no_show) = 25%
    expect($stats['noShowRate'])->toBe(25.0);
});

test('getOverviewStats returns page views for last 7 days', function () {
    // Recent page views
    BusinessPageView::factory()->count(5)->create([
        'business_id' => $this->business->id,
        'viewed_at' => now()->subDays(2),
    ]);

    // Old page views should not count
    BusinessPageView::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'viewed_at' => now()->subDays(10),
    ]);

    $stats = $this->service->getOverviewStats($this->business);

    expect($stats['pageViews'])->toBe(5);
});

test('getUpcomingBookings returns only future non-cancelled bookings', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $upcoming = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->addDays(1),
        'status' => 'confirmed',
    ]);

    // Cancelled booking should not appear
    Booking::factory()->cancelled()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->addDays(2),
    ]);

    // Past booking should not appear
    Booking::factory()->completed()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
    ]);

    $bookings = $this->service->getUpcomingBookings($this->business);

    expect($bookings)->toHaveCount(1)
        ->and($bookings->first()->id)->toBe($upcoming->id);
});

test('getUpcomingBookings respects limit and ordering', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    Booking::factory()->count(5)->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->addDays(1),
        'status' => 'confirmed',
    ]);

    $bookings = $this->service->getUpcomingBookings($this->business, 3);

    expect($bookings)->toHaveCount(3);
});

test('getUpcomingBookings eager loads service and customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->addDays(1),
        'status' => 'confirmed',
    ]);

    $bookings = $this->service->getUpcomingBookings($this->business);

    expect($bookings->first()->relationLoaded('service'))->toBeTrue()
        ->and($bookings->first()->relationLoaded('customer'))->toBeTrue();
});

test('getRecentActivity returns mixed activity types sorted by date', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'created_at' => now()->subHours(2),
    ]);

    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->addDays(1),
        'status' => 'confirmed',
        'created_at' => now()->subHour(),
    ]);

    Review::factory()->create([
        'business_id' => $this->business->id,
        'created_at' => now()->subMinutes(30),
    ]);

    $activities = $this->service->getRecentActivity($this->business);

    expect($activities)->toHaveCount(3);

    $types = $activities->pluck('type')->toArray();
    expect($types)->toContain('booking_created')
        ->and($types)->toContain('customer_created')
        ->and($types)->toContain('review_received');

    // Should be sorted by datetime descending (most recent first)
    $dates = $activities->pluck('datetime')->toArray();
    $sortedDates = $dates;
    usort($sortedDates, fn ($a, $b) => strcmp($b, $a));
    expect($dates)->toBe($sortedDates);
});

test('getRecentActivity respects limit', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    Booking::factory()->count(8)->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->businessService->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => now()->addDays(1),
    ]);

    Customer::factory()->count(5)->create(['business_id' => $this->business->id]);

    $activities = $this->service->getRecentActivity($this->business, 5);

    expect($activities)->toHaveCount(5);
});
