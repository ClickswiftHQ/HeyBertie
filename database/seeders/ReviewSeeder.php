<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = Business::where('verification_status', 'verified')->get();

        $reviewTexts = [
            5 => [
                'Absolutely fantastic! My dog has never looked so good. The staff were so patient and caring.',
                'Best grooming experience we\'ve had. Buddy was so relaxed and came out looking amazing!',
                'Incredible service! They really know how to handle anxious dogs. Highly recommend.',
                'Five stars isn\'t enough! Professional, kind, and my poodle looks like a show dog.',
            ],
            4 => [
                'Very happy with the grooming. Dog looks great and the team were friendly.',
                'Good service overall. My dog enjoyed it and the price was fair.',
                'Professional and thorough. Only slightly late starting but great results.',
            ],
            3 => [
                'Decent grooming but the wait was longer than expected. Results were okay.',
                'Service was fine but nothing special. Would try again though.',
            ],
            2 => [
                'Wasn\'t thrilled with the cut. It looked a bit uneven. Staff were nice though.',
            ],
        ];

        foreach ($businesses as $business) {
            $completedBookings = Booking::where('business_id', $business->id)
                ->where('status', 'completed')
                ->get();

            $reviewCount = min($completedBookings->count(), fake()->numberBetween(8, 20));

            for ($i = 0; $i < $reviewCount; $i++) {
                $rating = fake()->randomElement([5, 5, 5, 4, 4, 4, 3, 5, 4, 5]);
                $booking = $completedBookings->count() > $i ? $completedBookings[$i] : null;

                $reviewer = User::factory()->create();

                $review = Review::create([
                    'business_id' => $business->id,
                    'booking_id' => $booking?->id,
                    'user_id' => $reviewer->id,
                    'rating' => $rating,
                    'review_text' => fake()->randomElement($reviewTexts[$rating] ?? $reviewTexts[4]),
                    'is_verified' => $booking !== null,
                    'is_published' => true,
                ]);

                // Some reviews get responses
                if (fake()->boolean(40)) {
                    $review->respond(
                        'Thank you so much for your kind feedback! We look forward to seeing you and your pup again soon.',
                        $business->owner
                    );
                }
            }
        }
    }
}
