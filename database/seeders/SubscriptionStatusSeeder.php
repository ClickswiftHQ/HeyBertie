<?php

namespace Database\Seeders;

use App\Models\SubscriptionStatus;
use Illuminate\Database\Seeder;

class SubscriptionStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Trial', 'slug' => 'trial', 'sort_order' => 1],
            ['name' => 'Active', 'slug' => 'active', 'sort_order' => 2],
            ['name' => 'Past Due', 'slug' => 'past_due', 'sort_order' => 3],
            ['name' => 'Cancelled', 'slug' => 'cancelled', 'sort_order' => 4],
            ['name' => 'Suspended', 'slug' => 'suspended', 'sort_order' => 5],
        ];

        foreach ($statuses as $status) {
            SubscriptionStatus::firstOrCreate(['slug' => $status['slug']], $status);
        }
    }
}
