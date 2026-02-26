<?php

namespace Database\Seeders;

use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class SubscriptionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Free', 'slug' => 'free', 'monthly_price_pence' => 0, 'staff_limit' => 0, 'location_limit' => 1, 'sms_quota' => 0, 'trial_days' => 0, 'sort_order' => 1],
            ['name' => 'Solo', 'slug' => 'solo', 'monthly_price_pence' => 1999, 'staff_limit' => 0, 'location_limit' => 1, 'sms_quota' => 30, 'trial_days' => 14, 'sort_order' => 2],
            ['name' => 'Salon', 'slug' => 'salon', 'monthly_price_pence' => 4999, 'staff_limit' => 5, 'location_limit' => 3, 'sms_quota' => 100, 'trial_days' => 14, 'sort_order' => 3],
        ];

        foreach ($tiers as $tier) {
            SubscriptionTier::firstOrCreate(['slug' => $tier['slug']], $tier);
        }
    }
}
