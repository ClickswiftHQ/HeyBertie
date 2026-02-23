<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('register.complete'));
});

test('stub user is upgraded on registration', function () {
    $stub = User::factory()->stub()->create(['email' => 'stub@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Real Name',
        'email' => 'stub@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('register.complete'));
    $this->assertAuthenticatedAs($stub->fresh());

    $stub->refresh();
    expect($stub->name)->toBe('Real Name')
        ->and($stub->is_registered)->toBeTrue()
        ->and($stub->email_verified_at)->toBeNull()
        ->and(Hash::check('new-password', $stub->password))->toBeTrue();

    // No duplicate user created
    expect(User::where('email', 'stub@example.com')->count())->toBe(1);
});

test('registered email returns same redirect without authenticating', function () {
    Notification::fake();

    $existing = User::factory()->create([
        'email' => 'taken@example.com',
        'name' => 'Original Name',
        'is_registered' => true,
    ]);
    $originalPassword = $existing->password;

    $response = $this->post(route('register.store'), [
        'name' => 'Attacker',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('register.complete'));
    $this->assertGuest();

    // Existing user is unmodified
    $existing->refresh();
    expect($existing->name)->toBe('Original Name')
        ->and($existing->password)->toBe($originalPassword);

    Notification::assertNothingSent();
});

test('stub user upgrade is case-insensitive on email', function () {
    $stub = User::factory()->stub()->create(['email' => 'McTesterson@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Real Name',
        'email' => 'mctesterson@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('register.complete'));
    $this->assertAuthenticatedAs($stub->fresh());

    $stub->refresh();
    expect($stub->is_registered)->toBeTrue();

    // No duplicate user created
    expect(User::whereRaw('LOWER(email) = ?', ['mctesterson@example.com'])->count())->toBe(1);
});

test('my-bookings index requires email verification', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('customer.bookings.index'));

    $response->assertRedirect(route('verification.notice'));
});
