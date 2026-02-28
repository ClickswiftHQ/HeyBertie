<?php

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->user->id]);
    $this->location = Location::factory()->create(['business_id' => $this->business->id]);
    $this->service = Service::factory()->create(['business_id' => $this->business->id]);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
});

test('guests are redirected to login', function () {
    $this->get("/{$this->business->handle}/calendar")
        ->assertRedirect(route('login'));
});

test('unauthorized users get 403', function () {
    $stranger = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->get("/{$this->business->handle}/calendar")
        ->assertForbidden();
});

test('renders calendar page with bookings and booking form data', function () {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/calendar")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/calendar/index')
            ->has('bookingGroups')
            ->has('statusCounts')
            ->has('filters')
            ->has('locations')
            ->has('services')
            ->has('recentCustomers')
        );
});

test('filters bookings by status', function () {
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'status' => 'pending',
        'appointment_datetime' => now()->addDay(),
    ]);
    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'status' => 'confirmed',
        'appointment_datetime' => now()->addDay(),
    ]);

    $from = now()->toDateString();
    $to = now()->addDays(7)->toDateString();

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/calendar?status=pending&from={$from}&to={$to}")
        ->assertInertia(fn ($page) => $page
            ->where('bookingGroups.0.bookings', fn ($bookings) => collect($bookings)->every(fn ($b) => $b['status'] === 'pending'))
        );
});

test('only shows bookings for current business', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $otherLocation = Location::factory()->create(['business_id' => $otherBusiness->id]);

    Booking::factory()->create([
        'business_id' => $otherBusiness->id,
        'location_id' => $otherLocation->id,
        'appointment_datetime' => now()->addDay(),
    ]);

    Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
        'appointment_datetime' => now()->addDay(),
    ]);

    $from = now()->toDateString();
    $to = now()->addDays(7)->toDateString();

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/calendar?from={$from}&to={$to}")
        ->assertInertia(fn ($page) => $page
            ->where('statusCounts.all', 1)
        );
});

test('can confirm a pending booking', function () {
    $booking = Booking::factory()->pending()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/calendar/{$booking->id}/confirm")
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('confirmed');
});

test('can cancel a booking with reason', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/calendar/{$booking->id}/cancel", [
            'cancellation_reason' => 'Customer requested',
        ])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
    expect($booking->cancellation_reason)->toBe('Customer requested');
});

test('can mark booking as completed', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/calendar/{$booking->id}/complete")
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('completed');
});

test('can mark booking as no-show', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/calendar/{$booking->id}/no-show")
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('no_show');
});

test('can update pro notes', function () {
    $booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $this->service->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/calendar/{$booking->id}/notes", [
            'pro_notes' => 'Dog was nervous',
        ])
        ->assertRedirect();

    expect($booking->fresh()->pro_notes)->toBe('Dog was nervous');
});

test('cannot modify bookings from another business', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $otherLocation = Location::factory()->create(['business_id' => $otherBusiness->id]);
    $booking = Booking::factory()->pending()->create([
        'business_id' => $otherBusiness->id,
        'location_id' => $otherLocation->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/calendar/{$booking->id}/confirm")
        ->assertNotFound();
});

// Manual booking tests

test('can create a manual booking with existing customer', function () {
    $customerUser = User::factory()->create(['email' => 'existing@example.com']);
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'user_id' => $customerUser->id,
        'email' => 'existing@example.com',
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

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [
            'location_id' => $this->location->id,
            'service_ids' => [$this->service->id],
            'appointment_datetime' => $nextMonday->copy()->setTime(10, 0)->toDateTimeString(),
            'customer_id' => $customer->id,
            'pet_name' => 'Buddy',
            'pet_breed' => 'Labrador',
            'pet_size' => 'large',
        ])
        ->assertRedirect();

    $booking = Booking::query()->latest('id')->first();
    expect($booking->customer_id)->toBe($customer->id);
    expect($booking->pet_name)->toBe('Buddy');
    expect($booking->pet_breed)->toBe('Labrador');
    expect($booking->business_id)->toBe($this->business->id);
});

test('can create a manual booking with new customer details', function () {
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

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [
            'location_id' => $this->location->id,
            'service_ids' => [$this->service->id],
            'appointment_datetime' => $nextMonday->copy()->setTime(10, 0)->toDateTimeString(),
            'name' => 'New Person',
            'email' => 'newperson@example.com',
            'phone' => '07700900000',
            'pet_name' => 'Rex',
        ])
        ->assertRedirect();

    $booking = Booking::query()->latest('id')->first();
    expect($booking->pet_name)->toBe('Rex');
    expect($booking->customer->name)->toBe('New Person');
    expect($booking->customer->email)->toBe('newperson@example.com');
});

test('manual booking sends confirmation email to customer', function () {
    Mail::fake();

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

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [
            'location_id' => $this->location->id,
            'service_ids' => [$this->service->id],
            'appointment_datetime' => $nextMonday->copy()->setTime(10, 0)->toDateTimeString(),
            'name' => 'Email Test',
            'email' => 'emailtest@example.com',
            'phone' => '07700900000',
            'pet_name' => 'Biscuit',
        ])
        ->assertRedirect();

    Mail::assertQueued(BookingConfirmation::class, fn ($mail) => $mail->hasTo('emailtest@example.com'));
    Mail::assertQueued(NewBookingNotification::class);
});

test('manual booking validates required fields', function () {
    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [])
        ->assertSessionHasErrors(['location_id', 'service_ids', 'appointment_datetime', 'pet_name']);
});

test('manual booking requires customer or name/email/phone', function () {
    $nextMonday = now()->next('Monday');

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [
            'location_id' => $this->location->id,
            'service_ids' => [$this->service->id],
            'appointment_datetime' => $nextMonday->copy()->setTime(10, 0)->toDateTimeString(),
            'pet_name' => 'Buddy',
        ])
        ->assertSessionHasErrors(['name', 'email', 'phone']);
});

test('manual booking scoped to business locations', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $otherLocation = Location::factory()->create(['business_id' => $otherBusiness->id]);
    $nextMonday = now()->next('Monday');

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [
            'location_id' => $otherLocation->id,
            'service_ids' => [$this->service->id],
            'appointment_datetime' => $nextMonday->copy()->setTime(10, 0)->toDateTimeString(),
            'customer_id' => $this->customer->id,
            'pet_name' => 'Buddy',
        ])
        ->assertNotFound();
});

test('manual booking scoped to business services', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $otherService = Service::factory()->create(['business_id' => $otherBusiness->id]);
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

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/calendar/manual", [
            'location_id' => $this->location->id,
            'service_ids' => [$otherService->id],
            'appointment_datetime' => $nextMonday->copy()->setTime(10, 0)->toDateTimeString(),
            'customer_id' => $this->customer->id,
            'pet_name' => 'Buddy',
        ])
        ->assertStatus(422);
});
