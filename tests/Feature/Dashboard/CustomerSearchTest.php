<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->user->id]);
});

test('guests are redirected to login', function () {
    $this->getJson("/{$this->business->handle}/customers/search?q=test")
        ->assertUnauthorized();
});

test('returns matching customers by name', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
    ]);
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'John Doe',
    ]);

    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=jane")
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Jane Smith']);
});

test('returns matching customers by email', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'buddy@example.com',
    ]);

    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=buddy@")
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['email' => 'buddy@example.com']);
});

test('returns matching customers by phone', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'phone' => '07700123456',
    ]);

    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=07700")
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['phone' => '07700123456']);
});

test('only returns customers belonging to the business', function () {
    $otherBusiness = Business::factory()->completed()->create();

    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Our Customer',
    ]);
    Customer::factory()->create([
        'business_id' => $otherBusiness->id,
        'name' => 'Our Other Customer',
    ]);

    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=Our")
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Our Customer']);
});

test('returns empty array for no matches', function () {
    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=nonexistent")
        ->assertSuccessful()
        ->assertJsonCount(0);
});

test('returns empty array when query is too short', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
    ]);

    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=J")
        ->assertSuccessful()
        ->assertJsonCount(0);
});

test('only returns active customers', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Active Customer',
        'is_active' => true,
    ]);
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Inactive Customer',
        'is_active' => false,
    ]);

    $this->actingAs($this->user)
        ->getJson("/{$this->business->handle}/customers/search?q=Customer")
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Active Customer']);
});
