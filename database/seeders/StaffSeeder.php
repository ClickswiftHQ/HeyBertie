<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\StaffMember;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $salonTier = SubscriptionTier::where('slug', 'salon')->firstOrFail();
        $staffRole = BusinessRole::where('slug', 'staff')->firstOrFail();

        $salonBusinesses = Business::where('subscription_tier_id', $salonTier->id)->get();

        foreach ($salonBusinesses as $business) {
            $locationIds = $business->locations()->pluck('id')->toArray();
            $staffCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $staffCount; $i++) {
                $user = User::factory()->create(['role' => 'pro']);

                StaffMember::create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'display_name' => $user->name,
                    'bio' => fake()->sentence(),
                    'role' => $i === 0 ? 'groomer' : fake()->randomElement(['groomer', 'assistant']),
                    'commission_rate' => fake()->randomElement([30, 35, 40, 45]),
                    'calendar_color' => fake()->randomElement(['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6']),
                    'working_locations' => $locationIds,
                    'accepts_online_bookings' => true,
                    'is_active' => true,
                    'employed_since' => fake()->dateTimeBetween('-2 years', '-1 month'),
                ]);

                $business->users()->attach($user->id, [
                    'business_role_id' => $staffRole->id,
                    'is_active' => true,
                    'accepted_at' => now(),
                ]);
            }
        }
    }
}
