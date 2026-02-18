<?php

namespace Database\Seeders;

use App\Models\AvailabilityBlock;
use App\Models\Business;
use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $paidTierIds = SubscriptionTier::whereIn('slug', ['solo', 'salon'])->pluck('id');
        $businesses = Business::whereIn('subscription_tier_id', $paidTierIds)->get();

        foreach ($businesses as $business) {
            // Mon-Fri working hours (day_of_week: 1=Mon, 5=Fri)
            for ($day = 1; $day <= 5; $day++) {
                AvailabilityBlock::create([
                    'business_id' => $business->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'block_type' => 'available',
                    'repeat_weekly' => true,
                ]);

                // Lunch break
                AvailabilityBlock::create([
                    'business_id' => $business->id,
                    'day_of_week' => $day,
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                    'block_type' => 'break',
                    'repeat_weekly' => true,
                    'notes' => 'Lunch break',
                ]);
            }

            // Saturday half day
            AvailabilityBlock::create([
                'business_id' => $business->id,
                'day_of_week' => 6, // Saturday
                'start_time' => '09:00',
                'end_time' => '13:00',
                'block_type' => 'available',
                'repeat_weekly' => true,
            ]);

            // Sunday blocked
            AvailabilityBlock::create([
                'business_id' => $business->id,
                'day_of_week' => 0, // Sunday
                'start_time' => '00:00',
                'end_time' => '23:59',
                'block_type' => 'blocked',
                'repeat_weekly' => true,
                'notes' => 'Closed on Sundays',
            ]);

            // One-off holiday
            AvailabilityBlock::create([
                'business_id' => $business->id,
                'specific_date' => now()->year.'-12-25',
                'start_time' => '00:00',
                'end_time' => '23:59',
                'block_type' => 'holiday',
                'repeat_weekly' => false,
                'notes' => 'Christmas Day',
            ]);
        }
    }
}
