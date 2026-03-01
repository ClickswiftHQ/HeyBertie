<?php

use App\Models\Business;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->business = Business::factory()->completed()->create(['owner_user_id' => $this->user->id]);
});

test('guests are redirected to login', function () {
    $this->get("/{$this->business->handle}/services")
        ->assertRedirect(route('login'));
});

test('unauthorized users get 403', function () {
    $stranger = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->get("/{$this->business->handle}/services")
        ->assertForbidden();
});

test('renders services index page', function () {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/services")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/services/index')
            ->has('services')
        );
});

test('shows services ordered by display_order', function () {
    Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Second', 'display_order' => 2]);
    Service::factory()->create(['business_id' => $this->business->id, 'name' => 'First', 'display_order' => 1]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/services")
        ->assertInertia(fn ($page) => $page
            ->has('services', 2)
            ->where('services.0.name', 'First')
            ->where('services.1.name', 'Second')
        );
});

test('does not show services from other businesses', function () {
    $otherBusiness = Business::factory()->completed()->create();
    Service::factory()->create(['business_id' => $otherBusiness->id]);
    Service::factory()->create(['business_id' => $this->business->id, 'name' => 'My Service']);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/services")
        ->assertInertia(fn ($page) => $page
            ->has('services', 1)
            ->where('services.0.name', 'My Service')
        );
});

test('can create a service', function () {
    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/services", [
            'name' => 'Full Groom',
            'description' => 'Complete grooming session',
            'duration_minutes' => 90,
            'price' => 45.00,
            'price_type' => 'fixed',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('services', [
        'business_id' => $this->business->id,
        'name' => 'Full Groom',
        'duration_minutes' => 90,
    ]);
});

test('validates required fields on create', function () {
    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/services", [])
        ->assertSessionHasErrors(['name', 'duration_minutes', 'price_type']);
});

test('can update a service', function () {
    $service = Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Old Name']);

    $this->actingAs($this->user)
        ->put("/{$this->business->handle}/services/{$service->id}", [
            'name' => 'New Name',
            'duration_minutes' => 60,
            'price' => 30.00,
            'price_type' => 'fixed',
        ])
        ->assertRedirect();

    expect($service->fresh()->name)->toBe('New Name');
});

test('can soft-delete a service', function () {
    $service = Service::factory()->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user)
        ->delete("/{$this->business->handle}/services/{$service->id}")
        ->assertRedirect();

    expect($service->fresh()->trashed())->toBeTrue();
});

test('can toggle service active status', function () {
    $service = Service::factory()->create(['business_id' => $this->business->id, 'is_active' => true]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/services/{$service->id}/toggle-active")
        ->assertRedirect();

    expect($service->fresh()->is_active)->toBeFalse();
});

test('can reorder services', function () {
    $service1 = Service::factory()->create(['business_id' => $this->business->id, 'display_order' => 0]);
    $service2 = Service::factory()->create(['business_id' => $this->business->id, 'display_order' => 1]);

    $this->actingAs($this->user)
        ->post("/{$this->business->handle}/services/reorder", [
            'order' => [$service2->id, $service1->id],
        ])
        ->assertRedirect();

    expect($service1->fresh()->display_order)->toBe(1);
    expect($service2->fresh()->display_order)->toBe(0);
});

test('cannot modify services belonging to another business', function () {
    $otherBusiness = Business::factory()->completed()->create();
    $service = Service::factory()->create(['business_id' => $otherBusiness->id]);

    $this->actingAs($this->user)
        ->put("/{$this->business->handle}/services/{$service->id}", [
            'name' => 'Hacked',
            'duration_minutes' => 60,
            'price_type' => 'fixed',
        ])
        ->assertNotFound();
});
