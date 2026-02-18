<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\User;

beforeEach(function () {
    $this->business = Business::factory()->solo()->verified()->create();
    $this->location = Location::factory()->create(['business_id' => $this->business->id]);
    $this->service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 60]);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
});

it('belongs to business, location, service, and customer', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    expect($booking->business)->toBeInstanceOf(Business::class)
        ->and($booking->location)->toBeInstanceOf(Location::class)
        ->and($booking->service)->toBeInstanceOf(Service::class)
        ->and($booking->customer)->toBeInstanceOf(Customer::class);
});

it('scopes to upcoming bookings', function () {
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->addDays(3),
        'status' => 'confirmed',
    ]);

    Booking::factory()->completed()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    expect(Booking::upcoming()->count())->toBe(1);
});

it('scopes to specific status', function () {
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'status' => 'confirmed',
    ]);

    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'status' => 'pending',
    ]);

    expect(Booking::status('confirmed')->count())->toBe(1)
        ->and(Booking::status('pending')->count())->toBe(1);
});

it('can be cancelled with reason', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->addDays(5),
        'status' => 'confirmed',
    ]);

    $booking->cancel($user, 'Changed my mind');

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($booking->fresh()->cancelled_by_user_id)->toBe($user->id)
        ->and($booking->fresh()->cancellation_reason)->toBe('Changed my mind');
});

it('cannot be cancelled if less than 24 hours notice', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->addHours(12),
        'status' => 'confirmed',
    ]);

    expect($booking->canBeCancelled())->toBeFalse();
});

it('can be marked as completed', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'status' => 'confirmed',
    ]);

    $booking->markAsCompleted();

    expect($booking->fresh()->status)->toBe('completed');
});

it('can be marked as no show', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'status' => 'confirmed',
    ]);

    $booking->markAsNoShow();

    expect($booking->fresh()->status)->toBe('no_show');
});

it('finds bookings needing reminders', function () {
    // Needs reminder: confirmed, within 24hrs, no reminder sent
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->addHours(20),
        'status' => 'confirmed',
        'reminder_sent_at' => null,
    ]);

    // Already reminded
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->addHours(20),
        'status' => 'confirmed',
        'reminder_sent_at' => now()->subHours(2),
    ]);

    expect(Booking::needsReminder()->count())->toBe(1);
});
