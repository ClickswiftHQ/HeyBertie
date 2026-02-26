<?php

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->business = Business::factory()->solo()->completed()->verified()->create();
    $this->location = Location::factory()->create([
        'business_id' => $this->business->id,
        'min_notice_hours' => 1,
        'advance_booking_days' => 60,
        'booking_buffer_minutes' => 15,
    ]);
    $this->service1 = Service::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Full Groom',
        'duration_minutes' => 60,
        'price' => 45.00,
    ]);
    $this->service2 = Service::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Nail Trim',
        'duration_minutes' => 15,
        'price' => 12.00,
    ]);

    // Set up availability for next week
    $nextMonday = now()->next('Monday');
    AvailabilityBlock::create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'day_of_week' => $nextMonday->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'block_type' => 'available',
        'repeat_weekly' => true,
    ]);
    $this->appointmentDatetime = $nextMonday->copy()->setTime(10, 0)->toDateTimeString();
});

it('creates a booking with multiple services', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id, $this->service2->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
        'pet_name' => 'Bella',
        'pet_breed' => 'Cockapoo',
        'pet_size' => 'medium',
        'notes' => 'Nervous around clippers',
    ])->assertSuccessful()
        ->assertJsonStructure(['success', 'booking_reference', 'redirect']);

    $booking = Booking::where('business_id', $this->business->id)->latest()->first();

    expect($booking)->not->toBeNull()
        ->and($booking->booking_reference)->toStartWith('BK-')
        ->and($booking->duration_minutes)->toBe(75)
        ->and((float) $booking->price)->toBe(57.00)
        ->and($booking->pet_name)->toBe('Bella')
        ->and($booking->pet_breed)->toBe('Cockapoo')
        ->and($booking->pet_size)->toBe('medium')
        ->and($booking->customer_notes)->toBe('Nervous around clippers')
        ->and($booking->status)->toBe('confirmed')
        ->and($booking->payment_status)->toBe('pending');

    expect(BookingItem::where('booking_id', $booking->id)->count())->toBe(2);
});

it('creates a booking with a single service', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '07700900001',
        'pet_name' => 'Rex',
    ])->assertSuccessful();

    $booking = Booking::where('business_id', $this->business->id)->latest()->first();

    expect($booking->service_id)->toBe($this->service1->id)
        ->and($booking->duration_minutes)->toBe(60)
        ->and((float) $booking->price)->toBe(45.00);
});

it('creates a guest user and customer record', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'New Guest',
        'email' => 'newguest@example.com',
        'phone' => '07700900002',
        'pet_name' => 'Buddy',
    ])->assertSuccessful();

    $user = User::where('email', 'newguest@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->is_registered)->toBeFalse()
        ->and($user->name)->toBe('New Guest');

    $customer = Customer::where('business_id', $this->business->id)
        ->where('user_id', $user->id)
        ->first();
    expect($customer)->not->toBeNull()
        ->and($customer->phone)->toBe('07700900002')
        ->and($customer->source)->toBe('online');
});

it('links to existing user when guest email matches', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'phone' => '07700900003',
        'pet_name' => 'Max',
    ])->assertSuccessful();

    expect(User::where('email', 'existing@example.com')->count())->toBe(1);

    $booking = Booking::where('business_id', $this->business->id)->latest()->first();
    $customer = Customer::find($booking->customer_id);
    expect($customer->user_id)->toBe($existingUser->id);
});

it('snapshots service details in booking items', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '07700900004',
        'pet_name' => 'Luna',
    ])->assertSuccessful();

    $booking = Booking::where('business_id', $this->business->id)->latest()->first();
    $item = BookingItem::where('booking_id', $booking->id)->first();

    expect($item->service_name)->toBe('Full Groom')
        ->and($item->duration_minutes)->toBe(60)
        ->and((float) $item->price)->toBe(45.00)
        ->and($item->service_id)->toBe($this->service1->id);
});

it('returns 409 when time slot is no longer available', function () {
    // Book the slot first
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service1->id,
        'customer_id' => $customer->id,
        'appointment_datetime' => $this->appointmentDatetime,
        'duration_minutes' => 60,
        'status' => 'confirmed',
    ]);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Late Booker',
        'email' => 'late@example.com',
        'phone' => '07700900005',
        'pet_name' => 'Charlie',
    ])->assertStatus(409)
        ->assertJson(['success' => false]);
});

it('validates required fields', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['service_ids', 'appointment_datetime', 'name', 'email', 'phone', 'pet_name']);
});

it('validates pet size is one of the allowed values', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Test',
        'email' => 'test@example.com',
        'phone' => '07700900006',
        'pet_name' => 'Fluffy',
        'pet_size' => 'huge',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['pet_size']);
});

it('creates a confirmed booking when auto-confirm is enabled (default)', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Auto Confirm',
        'email' => 'autoconfirm@example.com',
        'phone' => '07700900010',
        'pet_name' => 'Buddy',
    ])->assertSuccessful();

    $booking = Booking::where('business_id', $this->business->id)->latest()->first();
    expect($booking->status)->toBe('confirmed');
});

it('creates a pending booking when auto-confirm is disabled', function () {
    $this->business->update(['settings' => ['auto_confirm_bookings' => false]]);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Manual Confirm',
        'email' => 'manualconfirm@example.com',
        'phone' => '07700900011',
        'pet_name' => 'Shadow',
    ])->assertSuccessful();

    $booking = Booking::where('business_id', $this->business->id)->latest()->first();
    expect($booking->status)->toBe('pending');
});

it('sends "Booking Received" email for pending bookings', function () {
    Mail::fake();
    $this->business->update(['settings' => ['auto_confirm_bookings' => false]]);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Pending Customer',
        'email' => 'pending@example.com',
        'phone' => '07700900012',
        'pet_name' => 'Daisy',
    ])->assertSuccessful();

    Mail::assertQueued(BookingConfirmation::class, function (BookingConfirmation $mail) {
        return str_contains($mail->envelope()->subject, 'Booking Received');
    });
});

it('sends "Booking Confirmed" email for auto-confirmed bookings', function () {
    Mail::fake();

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Confirmed Customer',
        'email' => 'confirmed@example.com',
        'phone' => '07700900013',
        'pet_name' => 'Duke',
    ])->assertSuccessful();

    Mail::assertQueued(BookingConfirmation::class, function (BookingConfirmation $mail) {
        return str_contains($mail->envelope()->subject, 'Booking Confirmed');
    });
});

it('sends "Action Required" email to business for pending bookings', function () {
    Mail::fake();
    $this->business->update(['settings' => ['auto_confirm_bookings' => false]]);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service1->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Action Customer',
        'email' => 'action@example.com',
        'phone' => '07700900014',
        'pet_name' => 'Rocky',
    ])->assertSuccessful();

    Mail::assertQueued(NewBookingNotification::class, function (NewBookingNotification $mail) {
        return str_contains($mail->envelope()->subject, 'Action Required');
    });
});

it('generates unique booking references', function () {
    $references = collect();
    for ($i = 0; $i < 10; $i++) {
        $references->push(Booking::generateReference());
    }

    expect($references->unique())->toHaveCount(10);
    $references->each(function ($ref) {
        expect($ref)->toMatch('/^BK-[A-Z0-9]{6}$/');
    });
});
