<?php

use App\Models\Business;
use App\Models\BusinessPet;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\User;
use App\Services\CustomerRegistrationService;

beforeEach(function () {
    $this->service = new CustomerRegistrationService;
});

it('creates a stub user and customer when no user exists', function () {
    $business = Business::factory()->create();

    $customer = $this->service->findOrCreateForBusiness($business, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '07700900000',
    ]);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->business_id)->toBe($business->id)
        ->and($customer->email)->toBe('jane@example.com')
        ->and($customer->user->is_registered)->toBeFalse();
});

it('reuses existing user when email matches', function () {
    $business = Business::factory()->create();
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $customer = $this->service->findOrCreateForBusiness($business, [
        'name' => 'Existing User',
        'email' => 'existing@example.com',
    ]);

    expect($customer->user_id)->toBe($existingUser->id)
        ->and(User::where('email', 'existing@example.com')->count())->toBe(1);
});

it('returns existing customer when user is already a customer at this business', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create(['email' => 'repeat@example.com']);
    $existingCustomer = Customer::factory()->create([
        'business_id' => $business->id,
        'user_id' => $user->id,
        'email' => 'repeat@example.com',
    ]);

    $customer = $this->service->findOrCreateForBusiness($business, [
        'name' => 'Repeat Customer',
        'email' => 'repeat@example.com',
    ]);

    expect($customer->id)->toBe($existingCustomer->id)
        ->and(Customer::where('business_id', $business->id)->count())->toBe(1);
});

it('creates separate customers at different businesses for the same user', function () {
    $business1 = Business::factory()->create();
    $business2 = Business::factory()->create();

    $customer1 = $this->service->findOrCreateForBusiness($business1, [
        'name' => 'Multi Biz',
        'email' => 'multi@example.com',
    ]);

    $customer2 = $this->service->findOrCreateForBusiness($business2, [
        'name' => 'Multi Biz',
        'email' => 'multi@example.com',
    ]);

    expect($customer1->user_id)->toBe($customer2->user_id)
        ->and($customer1->business_id)->not->toBe($customer2->business_id)
        ->and(User::where('email', 'multi@example.com')->count())->toBe(1);
});

it('sets source from data when creating a customer', function () {
    $business = Business::factory()->create();

    $customer = $this->service->findOrCreateForBusiness($business, [
        'name' => 'Walk In',
        'email' => 'walkin@example.com',
        'source' => 'walk_in',
    ]);

    expect($customer->source)->toBe('walk_in');
});

it('defaults source to online', function () {
    $business = Business::factory()->create();

    $customer = $this->service->findOrCreateForBusiness($business, [
        'name' => 'Online Customer',
        'email' => 'online@example.com',
    ]);

    expect($customer->source)->toBe('online');
});

it('upgrades a stub user to registered', function () {
    $stubUser = User::factory()->create(['is_registered' => false]);

    $upgraded = $this->service->upgradeStubUser($stubUser, [
        'password' => 'securepassword123',
        'name' => 'Updated Name',
    ]);

    expect($upgraded->is_registered)->toBeTrue()
        ->and($upgraded->name)->toBe('Updated Name')
        ->and($upgraded->email_verified_at)->not->toBeNull();
});

it('throws when upgrading an already registered user', function () {
    $registeredUser = User::factory()->create(['is_registered' => true]);

    $this->service->upgradeStubUser($registeredUser, [
        'password' => 'newpassword',
    ]);
})->throws(\RuntimeException::class, 'User is already registered.');

it('keeps existing name when upgrade data omits name', function () {
    $stubUser = User::factory()->create([
        'is_registered' => false,
        'name' => 'Original Name',
    ]);

    $upgraded = $this->service->upgradeStubUser($stubUser, [
        'password' => 'securepassword123',
    ]);

    expect($upgraded->name)->toBe('Original Name');
});

it('links a pet to a business with notes', function () {
    $pet = Pet::factory()->create();
    $business = Business::factory()->create();

    $businessPet = $this->service->linkPetToBusiness($pet, $business, [
        'notes' => 'Nervous around loud noises',
        'difficulty_rating' => 4,
    ]);

    expect($businessPet)->toBeInstanceOf(BusinessPet::class)
        ->and($businessPet->notes)->toBe('Nervous around loud noises')
        ->and($businessPet->difficulty_rating)->toBe(4)
        ->and($businessPet->last_seen_at)->not->toBeNull();
});

it('updates existing business pet link on re-link', function () {
    $pet = Pet::factory()->create();
    $business = Business::factory()->create();

    $this->service->linkPetToBusiness($pet, $business, [
        'notes' => 'First visit notes',
        'difficulty_rating' => 2,
    ]);

    $updated = $this->service->linkPetToBusiness($pet, $business, [
        'notes' => 'Updated notes',
        'difficulty_rating' => 3,
    ]);

    expect(BusinessPet::where('business_id', $business->id)->where('pet_id', $pet->id)->count())->toBe(1)
        ->and($updated->notes)->toBe('Updated notes')
        ->and($updated->difficulty_rating)->toBe(3);
});
