<?php

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\User;

test('guests are redirected to login from handle dashboard', function () {
    $business = Business::factory()->completed()->create();

    $this->get("/{$business->handle}/dashboard")
        ->assertRedirect(route('login'));
});

test('unverified users are redirected', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertRedirect(route('verification.notice'));
});

test('users without business access get 403', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $stranger = User::factory()->create(['email_verified_at' => now()]);

    // Give the stranger a completed business so onboarding middleware passes
    Business::factory()->completed()->create(['owner_user_id' => $stranger->id]);

    $business = Business::factory()->completed()->create(['owner_user_id' => $owner->id]);

    $this->actingAs($stranger)
        ->get("/{$business->handle}/dashboard")
        ->assertForbidden();
});

test('business owner can view dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/index')
            ->has('stats')
            ->has('upcomingBookings')
            ->has('recentActivity')
        );
});

test('staff member can view dashboard', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $staff = User::factory()->create(['email_verified_at' => now()]);

    // Give the staff member their own completed business so onboarding middleware passes
    Business::factory()->completed()->create(['owner_user_id' => $staff->id]);

    $business = Business::factory()->completed()->create(['owner_user_id' => $owner->id]);
    $staffRole = BusinessRole::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'sort_order' => 3]);
    $business->users()->attach($staff->id, ['business_role_id' => $staffRole->id]);

    $this->actingAs($staff)
        ->get("/{$business->handle}/dashboard")
        ->assertSuccessful();
});

test('dashboard redirect goes to primary business dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $business = Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('business.dashboard', $business->handle));
});

test('dashboard redirect goes to most recent business', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create([
        'owner_user_id' => $user->id,
        'created_at' => now()->subDay(),
    ]);
    $newer = Business::factory()->completed()->create([
        'owner_user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('business.dashboard', $newer->handle));
});

test('dashboard redirect goes to onboarding if no completed business', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.index'));
});

test('non-existent handle returns 404', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/nonexistent-handle/dashboard')
        ->assertNotFound();
});

test('inactive business returns 404', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $user->id]);
    $inactive = Business::factory()->completed()->create([
        'owner_user_id' => $user->id,
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get("/{$inactive->handle}/dashboard")
        ->assertNotFound();
});

test('incomplete onboarding business returns 404 for different user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Business::factory()->completed()->create(['owner_user_id' => $user->id]);

    // Draft owned by a different user — not caught by EnsureOnboardingComplete for $user
    $otherUser = User::factory()->create();
    $draft = Business::factory()->create([
        'owner_user_id' => $otherUser->id,
        'onboarding_completed' => false,
    ]);

    $this->actingAs($user)
        ->get("/{$draft->handle}/dashboard")
        ->assertNotFound();
});
