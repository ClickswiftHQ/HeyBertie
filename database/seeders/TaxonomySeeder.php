<?php

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\SizeCategory;
use App\Models\Species;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSpecies();
        $this->seedSizeCategories();
        $this->seedBreeds();
    }

    private function seedSpecies(): void
    {
        $species = ['Dog', 'Cat', 'Rabbit', 'Guinea Pig', 'Hamster'];

        foreach ($species as $index => $name) {
            Species::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index + 1],
            );
        }
    }

    private function seedSizeCategories(): void
    {
        $sizes = ['Small', 'Medium', 'Large', 'Giant'];

        foreach ($sizes as $index => $name) {
            SizeCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index + 1],
            );
        }
    }

    private function seedBreeds(): void
    {
        $dog = Species::where('slug', 'dog')->first();
        $cat = Species::where('slug', 'cat')->first();

        $dogBreeds = [
            'Golden Retriever', 'Labrador', 'Poodle', 'Cockapoo', 'French Bulldog',
            'Shih Tzu', 'Cavapoo', 'Border Collie', 'Dachshund', 'Springer Spaniel',
            'Cocker Spaniel', 'German Shepherd', 'Yorkshire Terrier', 'Bichon Frise',
            'Maltese', 'West Highland Terrier', 'Schnauzer', 'Husky',
        ];

        foreach ($dogBreeds as $index => $name) {
            Breed::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'species_id' => $dog->id, 'sort_order' => $index + 1],
            );
        }

        $catBreeds = ['Persian', 'Maine Coon', 'British Shorthair', 'Ragdoll', 'Bengal'];

        foreach ($catBreeds as $index => $name) {
            Breed::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'species_id' => $cat->id, 'sort_order' => $index + 1],
            );
        }
    }
}
