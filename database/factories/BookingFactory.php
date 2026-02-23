<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = Service::factory();

        return [
            'business_id' => Business::factory(),
            'location_id' => Location::factory(),
            'service_id' => $service,
            'customer_id' => Customer::factory(),
            'appointment_datetime' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'status' => 'confirmed',
            'booking_reference' => fn () => Booking::generateReference(),
            'price' => fake()->randomFloat(2, 15, 80),
            'deposit_amount' => 0,
            'deposit_paid' => false,
            'payment_status' => 'pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_datetime' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_datetime' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => 'no_show',
        ]);
    }

    public function withDeposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'deposit_amount' => 10.00,
            'deposit_paid' => true,
            'payment_status' => 'deposit_paid',
        ]);
    }
}
