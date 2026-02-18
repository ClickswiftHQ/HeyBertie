<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            ['name' => 'Full Groom', 'duration' => 90, 'price' => 45.00],
            ['name' => 'Bath & Brush', 'duration' => 60, 'price' => 30.00],
            ['name' => 'Nail Trim', 'duration' => 15, 'price' => 12.00],
            ['name' => 'Teeth Cleaning', 'duration' => 30, 'price' => 25.00],
            ['name' => 'Puppy Introduction', 'duration' => 45, 'price' => 30.00],
        ];

        $service = fake()->randomElement($services);

        return [
            'business_id' => Business::factory(),
            'name' => $service['name'],
            'description' => fake()->sentence(),
            'duration_minutes' => $service['duration'],
            'price' => $service['price'],
            'price_type' => 'fixed',
            'display_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function fromPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price_type' => 'from',
        ]);
    }

    public function callForQuote(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
            'price_type' => 'call',
        ]);
    }
}
