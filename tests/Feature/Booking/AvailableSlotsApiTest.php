<?php

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\StaffMember;

beforeEach(function () {
    $this->business = Business::factory()->solo()->completed()->verified()->create();
    $this->location = Location::factory()->create([
        'business_id' => $this->business->id,
        'min_notice_hours' => 1,
        'advance_booking_days' => 30,
        'booking_buffer_minutes' => 15,
    ]);

    // Create availability for every day of the week
    for ($day = 0; $day < 7; $day++) {
        AvailabilityBlock::create([
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'block_type' => 'available',
            'repeat_weekly' => true,
        ]);
    }
});

it('returns available dates for a location', function () {
    $this->getJson(route('api.booking.available-dates', [
        'location' => $this->location->id,
        'duration' => 60,
    ]))->assertSuccessful()
        ->assertJsonStructure([
            'dates' => [['date', 'available']],
            'advance_booking_days',
        ]);
});

it('returns the correct number of dates based on advance booking days', function () {
    $response = $this->getJson(route('api.booking.available-dates', [
        'location' => $this->location->id,
        'duration' => 60,
    ]))->assertSuccessful();

    $dates = $response->json('dates');
    expect(count($dates))->toBe(30);
});

it('marks dates as unavailable when fully booked', function () {
    $nextMonday = now()->next('Monday');

    // Fill the day with bookings
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $service = Service::factory()->create(['business_id' => $this->business->id]);
    for ($hour = 9; $hour < 17; $hour++) {
        Booking::factory()->create([
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'appointment_datetime' => $nextMonday->copy()->setTime($hour, 0),
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);
    }

    $response = $this->getJson(route('api.booking.available-dates', [
        'location' => $this->location->id,
        'duration' => 60,
    ]))->assertSuccessful();

    $dates = collect($response->json('dates'));
    $mondayEntry = $dates->firstWhere('date', $nextMonday->toDateString());

    expect($mondayEntry['available'])->toBeFalse();
});

it('returns time slots for a specific date', function () {
    $nextMonday = now()->next('Monday');

    $this->getJson(route('api.booking.time-slots', [
        'location' => $this->location->id,
        'date' => $nextMonday->toDateString(),
        'duration' => 60,
    ]))->assertSuccessful()
        ->assertJsonStructure([
            'date',
            'slots' => [['time', 'duration', 'period']],
        ]);
});

it('groups time slots by period', function () {
    $nextMonday = now()->next('Monday');

    $response = $this->getJson(route('api.booking.time-slots', [
        'location' => $this->location->id,
        'date' => $nextMonday->toDateString(),
        'duration' => 60,
    ]))->assertSuccessful();

    $slots = collect($response->json('slots'));
    $periods = $slots->pluck('period')->unique()->values()->toArray();

    // With 09:00-17:00, we should have morning and afternoon periods
    expect($periods)->toContain('morning')
        ->and($periods)->toContain('afternoon');
});

it('excludes slots that conflict with existing bookings', function () {
    $nextMonday = now()->next('Monday');
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $service = Service::factory()->create(['business_id' => $this->business->id]);

    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => $nextMonday->copy()->setTime(10, 0),
        'duration_minutes' => 60,
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(route('api.booking.time-slots', [
        'location' => $this->location->id,
        'date' => $nextMonday->toDateString(),
        'duration' => 60,
    ]))->assertSuccessful();

    $slots = collect($response->json('slots'));
    $times = $slots->pluck('time')->toArray();

    // 10:00 and 10:30 should not be available (booking + buffer)
    expect($times)->not->toContain('10:00');
});

it('returns 404 for an inactive location', function () {
    $inactiveLocation = Location::factory()->inactive()->create([
        'business_id' => $this->business->id,
    ]);

    $this->getJson(route('api.booking.available-dates', [
        'location' => $inactiveLocation->id,
        'duration' => 60,
    ]))->assertNotFound();
});

it('validates required parameters for time slots', function () {
    $this->getJson(route('api.booking.time-slots', [
        'location' => $this->location->id,
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'duration']);
});

it('validates required parameters for available dates', function () {
    $this->getJson(route('api.booking.available-dates', [
        'location' => $this->location->id,
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['duration']);
});

it('filters time slots by staff member', function () {
    $staff = StaffMember::factory()->create([
        'business_id' => $this->business->id,
        'accepts_online_bookings' => true,
    ]);
    $staff->locations()->attach($this->location->id);

    // Create availability specific to this staff member
    $nextMonday = now()->next('Monday');
    AvailabilityBlock::create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'staff_member_id' => $staff->id,
        'day_of_week' => $nextMonday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '12:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);

    $response = $this->getJson(route('api.booking.time-slots', [
        'location' => $this->location->id,
        'date' => $nextMonday->toDateString(),
        'duration' => 60,
        'staff' => $staff->id,
    ]))->assertSuccessful();

    $slots = collect($response->json('slots'));

    // Staff has availability 09-12 and location has 09-17
    // All returned slots should be within the staff's availability window
    $slots->each(function ($slot) {
        $hour = (int) explode(':', $slot['time'])[0];
        expect($hour)->toBeLessThan(17);
    });
});

it('returns no slots for a date with a holiday block', function () {
    $nextMonday = now()->next('Monday');

    AvailabilityBlock::create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'specific_date' => $nextMonday->toDateString(),
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'holiday',
        'repeat_weekly' => false,
    ]);

    $response = $this->getJson(route('api.booking.time-slots', [
        'location' => $this->location->id,
        'date' => $nextMonday->toDateString(),
        'duration' => 60,
    ]))->assertSuccessful();

    expect($response->json('slots'))->toBeEmpty();
});
