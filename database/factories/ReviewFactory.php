<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reviewTexts = [
            'Absolutely fantastic! My dog looks amazing and was so calm throughout.',
            'Great service, very professional. Buddy loved it!',
            'My poodle has never looked better. Will definitely be coming back.',
            'Wonderful experience. The groomer was so patient with my nervous pup.',
            'Very happy with the results. Fair pricing too.',
            'Good grooming but had to wait a bit longer than expected.',
            'Excellent attention to detail. Highly recommend!',
        ];

        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->randomElement([4, 4, 5, 5, 5, 3, 5, 4]),
            'review_text' => fake()->randomElement($reviewTexts),
            'is_verified' => true,
            'is_published' => true,
        ];
    }

    public function withResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_text' => 'Thank you so much for your kind words! We look forward to seeing you again.',
            'responded_by_user_id' => User::factory(),
            'responded_at' => now(),
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
            'flag_reason' => 'Suspected fake review',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'booking_id' => null,
        ]);
    }
}
