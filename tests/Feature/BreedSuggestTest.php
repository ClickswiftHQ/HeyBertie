<?php

use App\Models\Breed;
use App\Models\Species;

beforeEach(function () {
    $dog = Species::create(['name' => 'Dog', 'slug' => 'dog', 'sort_order' => 1]);
    $cat = Species::create(['name' => 'Cat', 'slug' => 'cat', 'sort_order' => 2]);

    Breed::create(['name' => 'Cockapoo', 'slug' => 'cockapoo', 'species_id' => $dog->id, 'sort_order' => 1]);
    Breed::create(['name' => 'Cocker Spaniel', 'slug' => 'cocker-spaniel', 'species_id' => $dog->id, 'sort_order' => 2]);
    Breed::create(['name' => 'Labrador', 'slug' => 'labrador', 'species_id' => $dog->id, 'sort_order' => 3]);
    Breed::create(['name' => 'Bengal', 'slug' => 'bengal', 'species_id' => $cat->id, 'sort_order' => 1]);
});

test('returns breeds matching partial input', function () {
    $this->getJson('/api/breed-suggest?q=cock')
        ->assertSuccessful()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => 'Cockapoo', 'species' => 'Dog'])
        ->assertJsonFragment(['name' => 'Cocker Spaniel', 'species' => 'Dog']);
});

test('returns empty for no matches', function () {
    $this->getJson('/api/breed-suggest?q=zzzzz')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

test('returns empty for blank input', function () {
    $this->getJson('/api/breed-suggest?q=')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

test('search is case insensitive', function () {
    $this->getJson('/api/breed-suggest?q=LABRADOR')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Labrador']);
});

test('includes species name in results', function () {
    $this->getJson('/api/breed-suggest?q=bengal')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Bengal', 'species' => 'Cat']);
});
