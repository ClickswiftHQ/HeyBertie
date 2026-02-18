<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $serviceTemplates = [
            ['name' => 'Full Groom', 'description' => 'Complete grooming including bath, blow dry, clip, and nail trim.', 'duration' => 90, 'price' => 45.00, 'type' => 'from', 'featured' => true],
            ['name' => 'Bath & Brush', 'description' => 'Thorough bath, blow dry, and brush out. Perfect for maintaining your dog between grooms.', 'duration' => 60, 'price' => 30.00, 'type' => 'fixed', 'featured' => false],
            ['name' => 'Nail Trim', 'description' => 'Quick and stress-free nail clipping.', 'duration' => 15, 'price' => 12.00, 'type' => 'fixed', 'featured' => false],
            ['name' => 'Teeth Cleaning', 'description' => 'Gentle teeth cleaning using pet-safe products.', 'duration' => 30, 'price' => 25.00, 'type' => 'fixed', 'featured' => false],
            ['name' => 'Puppy Introduction', 'description' => 'A gentle first grooming experience for puppies under 6 months.', 'duration' => 45, 'price' => 30.00, 'type' => 'fixed', 'featured' => true],
            ['name' => 'De-matting Treatment', 'description' => 'Careful removal of mats and tangles. Price varies by severity.', 'duration' => 60, 'price' => 35.00, 'type' => 'from', 'featured' => false],
            ['name' => 'Show Preparation', 'description' => 'Professional show-quality grooming and styling. Contact us for breed-specific pricing.', 'duration' => 120, 'price' => null, 'type' => 'call', 'featured' => false],
        ];

        $businesses = Business::all();

        foreach ($businesses as $business) {
            $order = 0;
            // Give each business 4-7 services
            $count = fake()->numberBetween(4, min(7, count($serviceTemplates)));
            $selected = fake()->randomElements($serviceTemplates, $count);

            foreach ($selected as $template) {
                // Add some price variation per business
                $priceVariation = $template['price'] ? $template['price'] + fake()->numberBetween(-5, 10) : null;

                Service::create([
                    'business_id' => $business->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'duration_minutes' => $template['duration'],
                    'price' => $priceVariation ? max(5, $priceVariation) : null,
                    'price_type' => $template['type'],
                    'display_order' => $order++,
                    'is_active' => true,
                    'is_featured' => $template['featured'],
                ]);
            }
        }
    }
}
