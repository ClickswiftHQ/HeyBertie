<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessPet>
 */
class BusinessPetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'pet_id' => Pet::factory(),
            'notes' => null,
            'difficulty_rating' => null,
            'last_seen_at' => null,
        ];
    }

    public function withDifficultyRating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_rating' => $rating,
        ]);
    }

    public function recentlySeen(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }
}
