<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        $this->call([
            SubscriptionTierSeeder::class,
            SubscriptionStatusSeeder::class,
            BusinessRoleSeeder::class,
            TaxonomySeeder::class,
            BusinessSeeder::class,
            LocationSeeder::class,
            ServiceSeeder::class,
            CustomerSeeder::class,
            PetSeeder::class,
            StaffSeeder::class,
            AvailabilitySeeder::class,
            BookingSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
