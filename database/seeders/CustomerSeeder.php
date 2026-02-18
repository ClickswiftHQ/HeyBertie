<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = Business::with('subscriptionTier')->get();

        foreach ($businesses as $business) {
            $count = match ($business->subscriptionTier->slug) {
                'salon' => fake()->numberBetween(30, 50),
                'solo' => fake()->numberBetween(15, 30),
                default => fake()->numberBetween(5, 10),
            };

            Customer::factory()
                ->count($count)
                ->sequence(fn ($sequence) => [
                    'business_id' => $business->id,
                    'user_id' => User::factory()->create()->id,
                    'email' => fake()->unique()->safeEmail(),
                ])
                ->create();
        }
    }
}
