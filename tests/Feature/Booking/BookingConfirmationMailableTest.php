<?php

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingNotification;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;

beforeEach(function () {
    $this->business = Business::factory()->solo()->completed()->verified()->create([
        'name' => 'Paws & Claws Grooming',
        'phone' => '01onal234567',
    ]);
    $this->location = Location::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Highbury Salon',
        'address_line_1' => '42 Station Road',
        'city' => 'London',
        'postcode' => 'N5 1AB',
    ]);
    $this->customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
    ]);
    $service = Service::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Full Groom',
        'duration_minutes' => 60,
        'price' => 45.00,
    ]);
    $this->booking = Booking::factory()->create([
        'business_id' => $this->business->id,
        'location_id' => $this->location->id,
        'service_id' => $service->id,
        'customer_id' => $this->customer->id,
        'booking_reference' => 'BK-TEST01',
        'duration_minutes' => 75,
        'price' => 57.00,
        'pet_name' => 'Bella',
        'pet_breed' => 'Cockapoo',
        'pet_size' => 'medium',
        'customer_notes' => 'Nervous around clippers',
    ]);
    BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'service_id' => $service->id,
        'service_name' => 'Full Groom',
        'duration_minutes' => 60,
        'price' => 45.00,
        'display_order' => 0,
    ]);
    BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'service_name' => 'Nail Trim',
        'duration_minutes' => 15,
        'price' => 12.00,
        'display_order' => 1,
    ]);

    $this->booking->load(['business', 'location', 'customer', 'staffMember', 'items']);
});

it('customer email renders booking reference', function () {
    $mailable = new BookingConfirmation($this->booking);

    $mailable->assertSeeInHtml('BK-TEST01');
});

it('customer email renders pet info', function () {
    $mailable = new BookingConfirmation($this->booking);

    $mailable->assertSeeInHtml('Bella')
        ->assertSeeInHtml('Cockapoo')
        ->assertSeeInHtml('Medium');
});

it('customer email renders services and prices', function () {
    $mailable = new BookingConfirmation($this->booking);

    $mailable->assertSeeInHtml('Full Groom')
        ->assertSeeInHtml('Nail Trim')
        ->assertSeeInHtml('45.00')
        ->assertSeeInHtml('12.00')
        ->assertSeeInHtml('57.00');
});

it('customer email renders business name and location', function () {
    $mailable = new BookingConfirmation($this->booking);

    $mailable->assertSeeInHtml('Paws & Claws Grooming')
        ->assertSeeInHtml('Highbury Salon')
        ->assertSeeInHtml('42 Station Road')
        ->assertSeeInHtml('N5 1AB');
});

it('customer email has correct subject', function () {
    $mailable = new BookingConfirmation($this->booking);

    $mailable->assertHasSubject('Booking Confirmed — BK-TEST01');
});

it('business email renders booking reference', function () {
    $mailable = new NewBookingNotification($this->booking);

    $mailable->assertSeeInHtml('BK-TEST01');
});

it('business email renders customer details', function () {
    $mailable = new NewBookingNotification($this->booking);

    $mailable->assertSeeInHtml('Jane Smith')
        ->assertSeeInHtml('jane@example.com')
        ->assertSeeInHtml('07700900000');
});

it('business email renders pet info and services', function () {
    $mailable = new NewBookingNotification($this->booking);

    $mailable->assertSeeInHtml('Bella')
        ->assertSeeInHtml('Cockapoo')
        ->assertSeeInHtml('Full Groom')
        ->assertSeeInHtml('Nail Trim');
});

it('business email renders customer notes', function () {
    $mailable = new NewBookingNotification($this->booking);

    $mailable->assertSeeInHtml('Nervous around clippers');
});

it('business email has correct subject', function () {
    $mailable = new NewBookingNotification($this->booking);

    $mailable->assertHasSubject('New Booking: BK-TEST01');
});
