<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->user->id]);
});

test('guests are redirected to login', function () {
    $this->get("/{$this->business->handle}/customers")
        ->assertRedirect(route('login'));
});

test('unauthorized users get 403', function () {
    $stranger = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->get("/{$this->business->handle}/customers")
        ->assertForbidden();
});

test('renders customers index with customers and filters props', function () {
    Customer::factory()->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/customers/index')
            ->has('customers.data', 1)
            ->has('filters')
            ->where('filters.search', '')
            ->where('filters.status', 'all')
        );
});

test('paginates customers at 15 per page', function () {
    Customer::factory()->count(20)->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 15)
            ->where('customers.last_page', 2)
            ->where('customers.total', 20)
        );
});

test('searches customers by name', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Alice Smith']);
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Bob Jones']);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers?search=Alice")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Alice Smith')
        );
});

test('searches customers by email', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'email' => 'alice@example.com']);
    Customer::factory()->create(['business_id' => $this->business->id, 'email' => 'bob@example.com']);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers?search=alice@")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.email', 'alice@example.com')
        );
});

test('searches customers by phone', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'phone' => '07123456789']);
    Customer::factory()->create(['business_id' => $this->business->id, 'phone' => '07987654321']);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers?search=07123")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.phone', '07123456789')
        );
});

test('filters active customers', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'is_active' => true]);
    Customer::factory()->create(['business_id' => $this->business->id, 'is_active' => false]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers?status=active")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.is_active', true)
        );
});

test('filters inactive customers', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'is_active' => true]);
    Customer::factory()->create(['business_id' => $this->business->id, 'is_active' => false]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers?status=inactive")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.is_active', false)
        );
});

test('does not show customers from other businesses', function () {
    $otherBusiness = Business::factory()->completed()->create();
    Customer::factory()->create(['business_id' => $otherBusiness->id]);
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'My Customer']);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'My Customer')
        );
});

test('search ignores query under 2 characters', function () {
    Customer::factory()->count(3)->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/customers?search=A")
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 3)
        );
});
