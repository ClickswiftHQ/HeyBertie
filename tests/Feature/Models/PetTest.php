<?php

use App\Models\Breed;
use App\Models\Pet;
use App\Models\SizeCategory;
use App\Models\Species;
use App\Models\User;

it('belongs to a user', function () {
    $pet = Pet::factory()->create();

    expect($pet->user)->toBeInstanceOf(User::class);
});

it('belongs to a species', function () {
    $pet = Pet::factory()->create();

    expect($pet->species)->toBeInstanceOf(Species::class);
});

it('optionally belongs to a breed', function () {
    $pet = Pet::factory()->withBreed()->create();

    expect($pet->breed)->toBeInstanceOf(Breed::class)
        ->and($pet->breed->species_id)->toBe($pet->species_id);
});

it('optionally belongs to a size category', function () {
    $pet = Pet::factory()->withSize()->create();

    expect($pet->sizeCategory)->toBeInstanceOf(SizeCategory::class);
});

it('breed belongs to species', function () {
    $species = Species::firstOrCreate(['slug' => 'dog'], ['name' => 'Dog', 'sort_order' => 1]);
    $breed = Breed::firstOrCreate(['slug' => 'labrador'], ['name' => 'Labrador', 'species_id' => $species->id, 'sort_order' => 1]);

    expect($breed->species)->toBeInstanceOf(Species::class)
        ->and($breed->species->id)->toBe($species->id);
});

it('user can have multiple pets', function () {
    $user = User::factory()->create();

    Pet::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->pets)->toHaveCount(3);
});

it('casts birthday to date', function () {
    $pet = Pet::factory()->create(['birthday' => '2020-06-15']);

    expect($pet->birthday)->toBeInstanceOf(\Carbon\CarbonImmutable::class)
        ->and($pet->birthday->format('Y-m-d'))->toBe('2020-06-15');
});

it('defaults is_active to true', function () {
    $pet = Pet::factory()->create();

    expect($pet->is_active)->toBeTrue();
});
