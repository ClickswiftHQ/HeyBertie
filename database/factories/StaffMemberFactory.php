<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffMember>
 */
class StaffMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'];

        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'display_name' => fake()->firstName(),
            'bio' => fake()->sentence(),
            'role' => 'groomer',
            'commission_rate' => fake()->randomElement([30, 35, 40, 45, 50]),
            'calendar_color' => fake()->randomElement($colors),
            'accepts_online_bookings' => true,
            'is_active' => true,
            'employed_since' => fake()->dateTimeBetween('-3 years', '-1 month'),
        ];
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
            'commission_rate' => 20,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'left_at' => now(),
        ]);
    }
}
