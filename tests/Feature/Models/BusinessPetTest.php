<?php

use App\Models\Business;
use App\Models\BusinessPet;
use App\Models\Pet;
use Illuminate\Database\QueryException;

it('belongs to a business', function () {
    $businessPet = BusinessPet::factory()->create();

    expect($businessPet->business)->toBeInstanceOf(Business::class);
});

it('belongs to a pet', function () {
    $businessPet = BusinessPet::factory()->create();

    expect($businessPet->pet)->toBeInstanceOf(Pet::class);
});

it('enforces unique constraint on business_id and pet_id', function () {
    $business = Business::factory()->create();
    $pet = Pet::factory()->create();

    BusinessPet::factory()->create(['business_id' => $business->id, 'pet_id' => $pet->id]);

    BusinessPet::factory()->create(['business_id' => $business->id, 'pet_id' => $pet->id]);
})->throws(QueryException::class);

it('casts last_seen_at to datetime', function () {
    $businessPet = BusinessPet::factory()->recentlySeen()->create();

    expect($businessPet->last_seen_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
});

it('casts difficulty_rating to integer', function () {
    $businessPet = BusinessPet::factory()->withDifficultyRating(3)->create();

    expect($businessPet->difficulty_rating)->toBe(3)
        ->and($businessPet->difficulty_rating)->toBeInt();
});

it('allows same pet to be linked to multiple businesses', function () {
    $pet = Pet::factory()->create();
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    BusinessPet::factory()->create(['pet_id' => $pet->id, 'business_id' => $business1->id]);
    BusinessPet::factory()->create(['pet_id' => $pet->id, 'business_id' => $business2->id]);

    expect($pet->businessNotes)->toHaveCount(2)
        ->and($pet->businesses)->toHaveCount(2);
});
