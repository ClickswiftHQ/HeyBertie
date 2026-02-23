<?php

use App\Mail\BookingConfirmation;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->business = Business::factory()->solo()->completed()->verified()->create();
    $this->location = Location::factory()->create([
        'business_id' => $this->business->id,
        'min_notice_hours' => 1,
        'advance_booking_days' => 60,
        'booking_buffer_minutes' => 15,
    ]);
    $this->service = Service::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Full Groom',
        'duration_minutes' => 60,
        'price' => 45.00,
    ]);

    $this->user = User::factory()->create(['is_registered' => true]);
    $this->customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'user_id' => $this->user->id,
    ]);

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

    $this->booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => $nextMonday->copy()->setTime(10, 0),
        'duration_minutes' => 60,
        'status' => 'confirmed',
        'price' => 45.00,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'service_id' => $this->service->id,
        'service_name' => 'Full Groom',
        'duration_minutes' => 60,
        'price' => 45.00,
    ]);

    $this->nextMonday = $nextMonday;
});

// === Index (My Bookings list) ===

it('shows bookings list for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('customer.bookings.index'))
        ->assertSuccessful()
        ->assertSee($this->business->name)
        ->assertSee($this->booking->booking_reference);
});

it('redirects unauthenticated users from bookings list', function () {
    $this->get(route('customer.bookings.index'))
        ->assertRedirect(route('login'));
});

// === Show (Booking detail) ===

it('shows booking detail via signed URL', function () {
    $url = URL::signedRoute('customer.bookings.show', ['ref' => $this->booking->booking_reference]);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee($this->booking->booking_reference)
        ->assertSee($this->business->name)
        ->assertSee('Full Groom');
});

it('shows booking detail for authenticated owner', function () {
    $this->actingAs($this->user)
        ->get(route('customer.bookings.show', ['ref' => $this->booking->booking_reference]))
        ->assertSuccessful()
        ->assertSee($this->booking->booking_reference);
});

it('denies booking detail for wrong authenticated user', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('customer.bookings.show', ['ref' => $this->booking->booking_reference]))
        ->assertForbidden();
});

it('denies booking detail without signed URL or auth', function () {
    $this->get(route('customer.bookings.show', ['ref' => $this->booking->booking_reference]))
        ->assertForbidden();
});

// === Cancel ===

it('cancels booking via signed URL', function () {
    $url = URL::signedRoute('customer.bookings.cancel', ['ref' => $this->booking->booking_reference]);

    $this->post($url, ['reason' => 'Changed my mind'])
        ->assertRedirect();

    $this->booking->refresh();
    expect($this->booking->status)->toBe('cancelled')
        ->and($this->booking->cancellation_reason)->toBe('Changed my mind');
});

it('cancels booking for authenticated owner', function () {
    $this->actingAs($this->user)
        ->post(route('customer.bookings.cancel', ['ref' => $this->booking->booking_reference]), [
            'reason' => 'No longer needed',
        ])
        ->assertRedirect();

    $this->booking->refresh();
    expect($this->booking->status)->toBe('cancelled');
});

it('blocks cancellation within 24 hours of appointment', function () {
    $this->booking->update(['appointment_datetime' => now()->addHours(12)]);

    $url = URL::signedRoute('customer.bookings.cancel', ['ref' => $this->booking->booking_reference]);

    $this->post($url)
        ->assertRedirect();

    $this->booking->refresh();
    expect($this->booking->status)->toBe('confirmed');
});

it('blocks cancellation of already cancelled booking', function () {
    $this->booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $url = URL::signedRoute('customer.bookings.cancel', ['ref' => $this->booking->booking_reference]);

    $this->post($url)
        ->assertRedirect();

    $this->booking->refresh();
    expect($this->booking->status)->toBe('cancelled');
});

// === Reschedule page ===

it('shows reschedule page via signed URL', function () {
    $url = URL::signedRoute('customer.bookings.reschedule', ['ref' => $this->booking->booking_reference]);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee('Reschedule Booking')
        ->assertSee($this->booking->booking_reference);
});

it('blocks reschedule page for cancelled booking', function () {
    $this->booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $url = URL::signedRoute('customer.bookings.reschedule', ['ref' => $this->booking->booking_reference]);

    $this->get($url)
        ->assertForbidden();
});

// === Process Reschedule ===

it('reschedules booking to a new time slot', function () {
    $newTime = $this->nextMonday->copy()->setTime(14, 0);

    $url = URL::signedRoute('customer.bookings.process-reschedule', ['ref' => $this->booking->booking_reference]);

    $this->post($url, [
        'appointment_datetime' => $newTime->toDateTimeString(),
    ])->assertRedirect();

    $this->booking->refresh();
    expect($this->booking->appointment_datetime->format('H:i'))->toBe('14:00');
});

it('blocks reschedule to unavailable time slot', function () {
    // Book a slot to make it unavailable
    $otherCustomer = Customer::factory()->create(['business_id' => $this->business->id]);
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'customer_id' => $otherCustomer->id,
        'appointment_datetime' => $this->nextMonday->copy()->setTime(14, 0),
        'duration_minutes' => 60,
        'status' => 'confirmed',
    ]);

    $url = URL::signedRoute('customer.bookings.process-reschedule', ['ref' => $this->booking->booking_reference]);

    $this->post($url, [
        'appointment_datetime' => $this->nextMonday->copy()->setTime(14, 0)->toDateTimeString(),
    ])->assertRedirect();

    // Booking should remain at original time
    $this->booking->refresh();
    expect($this->booking->appointment_datetime->format('H:i'))->toBe('10:00');
});

it('blocks reschedule for cancelled booking', function () {
    $this->booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $url = URL::signedRoute('customer.bookings.process-reschedule', ['ref' => $this->booking->booking_reference]);

    $this->post($url, [
        'appointment_datetime' => $this->nextMonday->copy()->setTime(14, 0)->toDateTimeString(),
    ])->assertRedirect();

    $this->booking->refresh();
    expect($this->booking->status)->toBe('cancelled');
});

// === Show page action visibility ===

it('shows cancel and reschedule buttons for upcoming booking', function () {
    $url = URL::signedRoute('customer.bookings.show', ['ref' => $this->booking->booking_reference]);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee('Cancel Booking')
        ->assertSee('Reschedule');
});

it('hides cancel and reschedule for past booking', function () {
    $this->booking->update([
        'appointment_datetime' => now()->subDay(),
        'status' => 'completed',
    ]);

    $url = URL::signedRoute('customer.bookings.show', ['ref' => $this->booking->booking_reference]);

    $this->get($url)
        ->assertSuccessful()
        ->assertDontSee('Cancel Booking')
        ->assertDontSee('Reschedule');
});

// === Confirmation email ===

it('includes manage booking URL in confirmation email', function () {
    Mail::fake();

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service->id],
        'appointment_datetime' => $this->nextMonday->copy()->setTime(15, 0)->toDateTimeString(),
        'name' => 'Test User',
        'email' => 'test-email@example.com',
        'phone' => '07700900099',
        'pet_name' => 'Buddy',
    ])->assertSuccessful();

    Mail::assertQueued(BookingConfirmation::class, function (BookingConfirmation $mail) {
        return str_contains($mail->manageUrl, '/my-bookings/')
            && str_contains($mail->manageUrl, 'signature=');
    });
});
