<?php

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\SizeCategory;
use App\Models\Species;
use Illuminate\Database\Seeder;

class PetSeeder extends Seeder
{
    public function run(): void
    {
        $petNames = ['Buddy', 'Luna', 'Max', 'Bella', 'Charlie', 'Daisy', 'Milo', 'Poppy', 'Oscar', 'Rosie', 'Teddy', 'Willow'];

        $dogSpecies = Species::where('slug', 'dog')->first();
        $dogBreeds = Breed::where('species_id', $dogSpecies->id)->get();
        $sizeCategories = SizeCategory::all();

        $customers = Customer::with('user')->get();

        foreach ($customers as $customer) {
            $petCount = fake()->numberBetween(1, 2);

            for ($i = 0; $i < $petCount; $i++) {
                Pet::create([
                    'user_id' => $customer->user_id,
                    'name' => fake()->randomElement($petNames),
                    'species_id' => $dogSpecies->id,
                    'breed_id' => fake()->boolean(80) ? $dogBreeds->random()->id : null,
                    'size_category_id' => fake()->boolean(70) ? $sizeCategories->random()->id : null,
                    'birthday' => fake()->boolean(60) ? fake()->dateTimeBetween('-15 years', '-6 months') : null,
                    'notes' => fake()->boolean(20) ? fake()->sentence() : null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
