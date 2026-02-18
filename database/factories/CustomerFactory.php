<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'source' => 'online',
            'marketing_consent' => fake()->boolean(70),
            'loyalty_points' => fake()->numberBetween(0, 200),
            'total_bookings' => fake()->numberBetween(0, 30),
            'total_spent' => fake()->randomFloat(2, 0, 1500),
            'is_active' => true,
        ];
    }

    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->state(['is_registered' => false]),
            'source' => 'walk_in',
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => fake()->numberBetween(100, 500),
            'total_bookings' => fake()->numberBetween(15, 50),
            'total_spent' => fake()->randomFloat(2, 500, 3000),
            'tags' => ['VIP', 'Regular'],
        ]);
    }

    public function newCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => 0,
            'total_bookings' => 0,
            'total_spent' => 0,
            'tags' => ['New'],
        ]);
    }
}
