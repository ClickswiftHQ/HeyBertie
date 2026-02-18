<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Location;
use App\Models\ServiceArea;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            // Muddy Paws - salon tier, multiple locations
            ['business' => 'muddy-paws', 'name' => 'Fulham Salon', 'slug' => 'fulham', 'type' => 'salon', 'address' => '45 Lillie Road', 'city' => 'London', 'postcode' => 'SW6 1UD', 'lat' => 51.4823, 'lng' => -0.1953, 'primary' => true],
            ['business' => 'muddy-paws', 'name' => 'Chelsea Branch', 'slug' => 'chelsea', 'type' => 'salon', 'address' => '112 Kings Road', 'city' => 'London', 'postcode' => 'SW3 4TW', 'lat' => 51.4879, 'lng' => -0.1660, 'primary' => false],

            // Dog House Spa - solo tier
            ['business' => 'dog-house-spa', 'name' => 'Manchester Salon', 'slug' => 'manchester', 'type' => 'salon', 'address' => '78 Deansgate', 'city' => 'Manchester', 'postcode' => 'M3 2FW', 'lat' => 53.4808, 'lng' => -2.2426, 'primary' => true],

            // Pampered Pooch - mobile
            ['business' => 'pampered-pooch', 'name' => 'Mobile Service - Greater Manchester', 'slug' => 'greater-manchester', 'type' => 'mobile', 'address' => '22 Oak Avenue', 'city' => 'Stockport', 'postcode' => 'SK1 3AA', 'lat' => 53.4106, 'lng' => -2.1575, 'primary' => true, 'mobile' => true, 'radius' => 15],

            // Bark & Beautiful - free tier
            ['business' => 'bark-beautiful', 'name' => 'Bristol Studio', 'slug' => 'bristol', 'type' => 'salon', 'address' => '15 Whiteladies Road', 'city' => 'Bristol', 'postcode' => 'BS8 1PB', 'lat' => 51.4585, 'lng' => -2.6066, 'primary' => true],

            // Wagging Tails - free tier
            ['business' => 'wagging-tails', 'name' => 'Leeds Salon', 'slug' => 'leeds', 'type' => 'home_based', 'address' => '8 Victoria Lane', 'city' => 'Leeds', 'postcode' => 'LS1 5AE', 'lat' => 53.7997, 'lng' => -1.5493, 'primary' => true],
        ];

        foreach ($locations as $data) {
            $business = Business::where('handle', $data['business'])->first();

            $location = Location::create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'location_type' => $data['type'],
                'address_line_1' => $data['address'],
                'city' => $data['city'],
                'postcode' => $data['postcode'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'is_primary' => $data['primary'],
                'is_mobile' => $data['mobile'] ?? false,
                'service_radius_km' => $data['radius'] ?? null,
                'is_active' => true,
                'accepts_bookings' => $business->subscription_tier !== 'free',
                'opening_hours' => [
                    'mon' => ['open' => '09:00', 'close' => '17:00'],
                    'tue' => ['open' => '09:00', 'close' => '17:00'],
                    'wed' => ['open' => '09:00', 'close' => '17:00'],
                    'thu' => ['open' => '09:00', 'close' => '18:00'],
                    'fri' => ['open' => '09:00', 'close' => '17:00'],
                    'sat' => ['open' => '09:00', 'close' => '13:00'],
                    'sun' => null,
                ],
            ]);

            // Add service areas for mobile locations
            if ($data['mobile'] ?? false) {
                $areas = [
                    ['area_name' => 'Stockport', 'postcode_prefix' => 'SK'],
                    ['area_name' => 'Manchester', 'postcode_prefix' => 'M'],
                    ['area_name' => 'Salford', 'postcode_prefix' => 'M'],
                    ['area_name' => 'Trafford', 'postcode_prefix' => 'M'],
                ];

                foreach ($areas as $area) {
                    ServiceArea::create([
                        'location_id' => $location->id,
                        'area_name' => $area['area_name'],
                        'postcode_prefix' => $area['postcode_prefix'],
                    ]);
                }
            }
        }
    }
}
