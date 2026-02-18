<?php

namespace Database\Factories;

use App\Models\Breed;
use App\Models\SizeCategory;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pet>
 */
class PetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $petNames = ['Buddy', 'Luna', 'Max', 'Bella', 'Charlie', 'Daisy', 'Milo', 'Poppy', 'Oscar', 'Rosie', 'Teddy', 'Willow'];

        $species = Species::firstOrCreate(
            ['slug' => 'dog'],
            ['name' => 'Dog', 'sort_order' => 1],
        );

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($petNames),
            'species_id' => $species->id,
            'breed_id' => null,
            'size_category_id' => null,
            'birthday' => fake()->dateTimeBetween('-15 years', '-6 months'),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function withBreed(): static
    {
        return $this->state(function (array $attributes) {
            $species = Species::find($attributes['species_id'])
                ?? Species::firstOrCreate(['slug' => 'dog'], ['name' => 'Dog', 'sort_order' => 1]);

            $breed = Breed::firstOrCreate(
                ['slug' => 'golden-retriever'],
                ['name' => 'Golden Retriever', 'species_id' => $species->id, 'sort_order' => 1],
            );

            return ['breed_id' => $breed->id];
        });
    }

    public function withSize(): static
    {
        return $this->state(function (array $attributes) {
            $size = SizeCategory::firstOrCreate(
                ['slug' => 'medium'],
                ['name' => 'Medium', 'sort_order' => 2],
            );

            return ['size_category_id' => $size->id];
        });
    }

    public function puppy(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthday' => fake()->dateTimeBetween('-1 year', '-2 months'),
        ]);
    }

    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthday' => fake()->dateTimeBetween('-15 years', '-8 years'),
        ]);
    }
}
