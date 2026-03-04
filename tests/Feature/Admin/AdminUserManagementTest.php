<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\Location;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
});

// --- User List ---

test('non-super user cannot access user list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

test('admin can view user list', function () {
    User::factory()->create();
    User::factory()->stub()->create();

    $this->actingAs($this->admin)
        ->get('/admin/users')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 3) // admin + 2 created
            ->has('filters')
        );
});

test('admin can search users by name', function () {
    User::factory()->create(['name' => 'Alice Wonderland']);
    User::factory()->create(['name' => 'Bob Builder']);

    $this->actingAs($this->admin)
        ->get('/admin/users?search=Alice')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('admin can search users by email', function () {
    User::factory()->create(['email' => 'alice@test.com']);
    User::factory()->create(['email' => 'bob@test.com']);

    $this->actingAs($this->admin)
        ->get('/admin/users?search=alice@test')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('admin can filter users by registered status', function () {
    User::factory()->create(); // registered
    User::factory()->stub()->create(); // guest

    $this->actingAs($this->admin)
        ->get('/admin/users?registered=0')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('admin can filter users by super status', function () {
    User::factory()->create(); // regular
    // admin is already super

    $this->actingAs($this->admin)
        ->get('/admin/users?super=1')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('admin can filter users with businesses', function () {
    $owner = User::factory()->create();
    Business::factory()->completed()->create(['owner_user_id' => $owner->id]);
    User::factory()->create(); // no business

    $this->actingAs($this->admin)
        ->get('/admin/users?has_businesses=1')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

// --- User Detail ---

test('admin can view user detail', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->has('user')
            ->has('ownedBusinesses')
            ->has('staffMemberships')
            ->has('pets')
            ->has('recentBookings')
            ->has('communications')
            ->has('timeline')
        );
});

test('user detail shows owned businesses', function () {
    $user = User::factory()->create();
    Business::factory()->solo()->completed()->verified()->create(['owner_user_id' => $user->id]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('ownedBusinesses', 1));
});

test('user detail shows pets', function () {
    $user = User::factory()->create();
    Pet::factory()->create(['user_id' => $user->id]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('pets', 1));
});

test('user detail shows bookings as customer', function () {
    $user = User::factory()->create();
    $business = Business::factory()->solo()->completed()->verified()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);
    $service = Service::factory()->create(['business_id' => $business->id]);
    $customer = Customer::factory()->create([
        'business_id' => $business->id,
        'user_id' => $user->id,
    ]);
    Booking::factory()->create([
        'business_id' => $business->id,
        'location_id' => $location->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('recentBookings', 1));
});

test('user detail shows communication history', function () {
    $user = User::factory()->create();
    EmailLog::create([
        'to_email' => $user->email,
        'email_type' => 'booking_confirmation',
        'subject' => 'Booking Confirmed',
        'status' => 'delivered',
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('communications', 1));
});

// --- Impersonation ---

test('admin can impersonate a regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/impersonate")
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('admin cannot impersonate another super admin', function () {
    $otherAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin)
        ->post("/admin/users/{$otherAdmin->id}/impersonate")
        ->assertRedirect();

    $this->assertAuthenticatedAs($this->admin);
});

test('admin cannot impersonate themselves', function () {
    $this->actingAs($this->admin)
        ->post("/admin/users/{$this->admin->id}/impersonate")
        ->assertRedirect();

    $this->assertAuthenticatedAs($this->admin);
});

test('can leave impersonation and restore original admin', function () {
    $user = User::factory()->create();

    // Start impersonation
    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/impersonate")
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);

    // Leave impersonation
    $this->post('/admin/impersonate/leave')
        ->assertRedirect('/admin/users');

    $this->assertAuthenticatedAs($this->admin);
});

test('impersonation session data is set correctly', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/impersonate");

    $this->assertAuthenticatedAs($user);

    // Session should have impersonation data
    expect(session('impersonating_from'))->toBe($this->admin->id)
        ->and(session('impersonating_from_name'))->toBe($this->admin->name);
});

// --- Reset Password ---

test('admin can reset user password', function () {
    $user = User::factory()->create();
    $oldPassword = $user->password;

    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->password)->not->toBe($oldPassword);
});

test('reset password requires confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ])
        ->assertSessionHasErrors('password');
});

test('reset password requires minimum length', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/reset-password", [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

// --- Toggle Super ---

// --- Activity Timeline ---

test('user detail includes activity timeline', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('timeline')
            ->where('timeline', fn ($timeline) => collect($timeline)->contains('type', 'account_created'))
        );
});

// --- Impersonation shared prop ---

test('impersonation data is shared via inertia when impersonating', function () {
    $user = User::factory()->create();

    // Start impersonation
    $this->actingAs($this->admin)
        ->post("/admin/users/{$user->id}/impersonate");

    // Access an inertia page as impersonated user — impersonating prop should be set
    // The admin dashboard requires super, so visit a non-admin inertia page
    $business = Business::factory()->solo()->completed()->verified()->create(['owner_user_id' => $user->id]);

    $this->get("/{$business->handle}/dashboard")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('impersonating.from_id', $this->admin->id)
            ->where('impersonating.from_name', $this->admin->name)
        );
});
