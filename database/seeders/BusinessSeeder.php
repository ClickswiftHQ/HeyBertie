<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $freeTier = SubscriptionTier::where('slug', 'free')->firstOrFail();
        $soloTier = SubscriptionTier::where('slug', 'solo')->firstOrFail();
        $salonTier = SubscriptionTier::where('slug', 'salon')->firstOrFail();

        $trialStatus = SubscriptionStatus::where('slug', 'trial')->firstOrFail();
        $activeStatus = SubscriptionStatus::where('slug', 'active')->firstOrFail();

        $ownerRole = BusinessRole::where('slug', 'owner')->firstOrFail();

        $businesses = [
            [
                'name' => 'Muddy Paws Grooming',
                'handle' => 'muddy-paws',
                'slug' => 'muddy-paws-grooming',
                'description' => 'Professional dog grooming in South West London. We treat every pup like our own.',
                'phone' => '020 7946 0958',
                'email' => 'hello@muddypaws.example.com',
                'subscription_tier_id' => $salonTier->id,
                'subscription_status_id' => $activeStatus->id,
                'verification_status' => 'verified',
                'verified_at' => now()->subMonths(3),
            ],
            [
                'name' => 'The Dog House Spa',
                'handle' => 'dog-house-spa',
                'slug' => 'the-dog-house-spa',
                'description' => 'Luxury grooming and spa treatments for dogs of all breeds.',
                'phone' => '0161 496 0532',
                'email' => 'bookings@doghouse.example.com',
                'subscription_tier_id' => $soloTier->id,
                'subscription_status_id' => $activeStatus->id,
                'verification_status' => 'verified',
                'verified_at' => now()->subMonths(2),
            ],
            [
                'name' => 'Pampered Pooch Mobile',
                'handle' => 'pampered-pooch',
                'slug' => 'pampered-pooch-mobile',
                'description' => 'Mobile dog grooming — we come to you! Serving Greater Manchester.',
                'phone' => '07700 900461',
                'email' => 'info@pamperedpooch.example.com',
                'subscription_tier_id' => $soloTier->id,
                'subscription_status_id' => $activeStatus->id,
                'verification_status' => 'verified',
                'verified_at' => now()->subMonth(),
            ],
            [
                'name' => 'Bark & Beautiful',
                'handle' => 'bark-beautiful',
                'slug' => 'bark-and-beautiful',
                'description' => 'Award-winning grooming salon in Bristol. Specialising in hand-stripping and breed-specific cuts.',
                'subscription_tier_id' => $freeTier->id,
                'subscription_status_id' => $trialStatus->id,
                'trial_ends_at' => now()->addDays(10),
                'verification_status' => 'pending',
            ],
            [
                'name' => 'Wagging Tails Studio',
                'handle' => 'wagging-tails',
                'slug' => 'wagging-tails-studio',
                'description' => 'Friendly neighbourhood grooming studio. Walk-ins welcome!',
                'subscription_tier_id' => $freeTier->id,
                'subscription_status_id' => $trialStatus->id,
                'trial_ends_at' => now()->addDays(5),
                'verification_status' => 'rejected',
                'verification_notes' => 'Unable to verify business registration. Please resubmit documentation.',
            ],
        ];

        foreach ($businesses as $data) {
            $owner = User::factory()->create([
                'name' => fake()->name(),
                'role' => 'pro',
            ]);

            $business = Business::create(array_merge($data, [
                'owner_user_id' => $owner->id,
                'is_active' => true,
            ]));

            $business->users()->attach($owner->id, [
                'business_role_id' => $ownerRole->id,
                'is_active' => true,
                'accepted_at' => now(),
            ]);
        }
    }
}
