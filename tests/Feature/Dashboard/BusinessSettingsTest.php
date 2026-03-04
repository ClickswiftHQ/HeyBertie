<?php

use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->solo()->completed()->verified()->create([
        'owner_user_id' => $this->user->id,
    ]);
});

test('owner can view settings page', function () {
    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/settings")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/settings/index')
            ->has('settings')
            ->where('settings.auto_confirm_bookings', true)
            ->where('settings.staff_selection_enabled', false)
        );
});

test('settings page reflects stored values', function () {
    $this->business->update([
        'settings' => ['auto_confirm_bookings' => false, 'staff_selection_enabled' => true],
    ]);

    $this->actingAs($this->user)
        ->get("/{$this->business->handle}/settings")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('settings.auto_confirm_bookings', false)
            ->where('settings.staff_selection_enabled', true)
        );
});

test('owner can update settings', function () {
    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/settings", [
            'auto_confirm_bookings' => false,
            'staff_selection_enabled' => true,
        ])
        ->assertRedirect();

    $this->business->refresh();
    expect($this->business->settings['auto_confirm_bookings'])->toBeFalse()
        ->and($this->business->settings['staff_selection_enabled'])->toBeTrue();
});

test('settings update preserves unrelated keys', function () {
    $this->business->update([
        'settings' => [
            'auto_confirm_bookings' => true,
            'staff_selection_enabled' => false,
            'deposits_enabled' => true,
            'deposit_type' => 'fixed',
        ],
    ]);

    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/settings", [
            'auto_confirm_bookings' => false,
            'staff_selection_enabled' => true,
        ])
        ->assertRedirect();

    $this->business->refresh();
    expect($this->business->settings['auto_confirm_bookings'])->toBeFalse()
        ->and($this->business->settings['staff_selection_enabled'])->toBeTrue()
        ->and($this->business->settings['deposits_enabled'])->toBeTrue()
        ->and($this->business->settings['deposit_type'])->toBe('fixed');
});

test('settings validation requires boolean values', function () {
    $this->actingAs($this->user)
        ->patch("/{$this->business->handle}/settings", [
            'auto_confirm_bookings' => 'not-a-boolean',
            'staff_selection_enabled' => 'invalid',
        ])
        ->assertSessionHasErrors(['auto_confirm_bookings', 'staff_selection_enabled']);
});

test('unauthenticated user cannot access settings', function () {
    $this->get("/{$this->business->handle}/settings")
        ->assertRedirect('/login');
});

test('non-owner cannot access settings', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get("/{$this->business->handle}/settings")
        ->assertForbidden();
});
