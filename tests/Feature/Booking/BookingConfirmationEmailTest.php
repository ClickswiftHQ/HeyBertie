<?php

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\AvailabilityBlock;
use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

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

it('sends confirmation email to customer after booking', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
        'pet_name' => 'Bella',
    ])->assertSuccessful();

    Mail::assertQueued(BookingConfirmation::class, function (BookingConfirmation $mail) {
        return $mail->hasTo('jane@example.com');
    });
});

it('sends notification email to business after booking', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
        'pet_name' => 'Bella',
    ])->assertSuccessful();

    Mail::assertQueued(NewBookingNotification::class);
});

it('sends business notification to location email when set', function () {
    $this->location->update(['email' => 'location@business.com']);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
        'pet_name' => 'Bella',
    ])->assertSuccessful();

    Mail::assertQueued(NewBookingNotification::class, function (NewBookingNotification $mail) {
        return $mail->hasTo('location@business.com');
    });
});

it('falls back to business email when location email is null', function () {
    $this->business->update(['email' => 'hello@business.com']);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
        'pet_name' => 'Bella',
    ])->assertSuccessful();

    Mail::assertQueued(NewBookingNotification::class, function (NewBookingNotification $mail) {
        return $mail->hasTo('hello@business.com');
    });
});

it('falls back to owner email when location and business emails are null', function () {
    $this->business->update(['email' => null]);

    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [
        'service_ids' => [$this->service->id],
        'appointment_datetime' => $this->appointmentDatetime,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
        'pet_name' => 'Bella',
    ])->assertSuccessful();

    Mail::assertQueued(NewBookingNotification::class, function (NewBookingNotification $mail) {
        return $mail->hasTo($this->business->owner->email);
    });
});

it('does not send emails when booking fails validation', function () {
    $this->postJson(route('booking.store', [$this->business->handle, $this->location->slug]), [])
        ->assertUnprocessable();

    Mail::assertNothingQueued();
});
