<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->city();

        return [
            'business_id' => Business::factory(),
            'name' => $city.' Salon',
            'slug' => Str::slug($city),
            'location_type' => 'salon',
            'address_line_1' => fake()->streetAddress(),
            'city' => $city,
            'postcode' => fake()->postcode(),
            'latitude' => fake()->latitude(51.3, 51.7),
            'longitude' => fake()->longitude(-0.5, 0.3),
            'is_mobile' => false,
            'is_primary' => true,
            'is_active' => true,
            'accepts_bookings' => true,
        ];
    }

    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Mobile Service - '.fake()->city(),
            'location_type' => 'mobile',
            'is_mobile' => true,
            'service_radius_km' => fake()->numberBetween(5, 20),
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'accepts_bookings' => false,
        ]);
    }
}
