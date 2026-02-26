<?php

namespace Database\Factories;

use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Grooming';
        $handle = Str::slug(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'handle' => $handle,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'subscription_tier_id' => fn () => SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'sort_order' => 1])->id,
            'subscription_status_id' => fn () => SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1])->id,
            'trial_ends_at' => now()->addDays(14),
            'verification_status' => 'pending',
            'is_active' => true,
            'owner_user_id' => User::factory(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function solo(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier_id' => fn () => SubscriptionTier::firstOrCreate(['slug' => 'solo'], ['name' => 'Solo', 'monthly_price_pence' => 1999, 'sms_quota' => 30, 'trial_days' => 14, 'sort_order' => 2])->id,
            'subscription_status_id' => fn () => SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2])->id,
        ]);
    }

    public function salon(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier_id' => fn () => SubscriptionTier::firstOrCreate(['slug' => 'salon'], ['name' => 'Salon', 'monthly_price_pence' => 4999, 'staff_limit' => 5, 'location_limit' => 3, 'sms_quota' => 100, 'trial_days' => 14, 'sort_order' => 3])->id,
            'subscription_status_id' => fn () => SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active', 'sort_order' => 2])->id,
        ]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status_id' => fn () => SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1])->id,
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_completed' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status_id' => fn () => SubscriptionStatus::firstOrCreate(['slug' => 'suspended'], ['name' => 'Suspended', 'sort_order' => 5])->id,
            'is_active' => false,
        ]);
    }
}
