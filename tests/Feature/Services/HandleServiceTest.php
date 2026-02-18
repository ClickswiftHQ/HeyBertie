<?php

use App\Models\Business;
use App\Models\HandleChange;
use App\Models\User;
use App\Rules\ValidHandle;
use App\Services\HandleService;

it('validates handle format', function () {
    $rule = new ValidHandle;

    $failed = false;
    $rule->validate('handle', 'valid-handle', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('rejects handles shorter than 3 characters', function () {
    $rule = new ValidHandle;
    $message = null;

    $rule->validate('handle', 'ab', function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toContain('between 3 and 30');
});

it('rejects handles with uppercase', function () {
    $rule = new ValidHandle;
    $failed = false;

    $rule->validate('handle', 'Invalid-Handle', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('rejects reserved words', function () {
    $rule = new ValidHandle;
    $failed = false;

    $rule->validate('handle', 'admin', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('rejects duplicate handles', function () {
    Business::factory()->create(['handle' => 'taken-handle']);

    $rule = new ValidHandle;
    $failed = false;

    $rule->validate('handle', 'taken-handle', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('allows excluding a business from uniqueness check', function () {
    $business = Business::factory()->create(['handle' => 'my-handle']);

    $rule = new ValidHandle($business->id);
    $failed = false;

    $rule->validate('handle', 'my-handle', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('changes a handle and creates audit trail', function () {
    $business = Business::factory()->create(['handle' => 'old-handle']);
    $user = $business->owner;

    $service = new HandleService;
    $service->changeHandle($business, 'new-handle', $user);

    expect($business->fresh()->handle)->toBe('new-handle')
        ->and(HandleChange::count())->toBe(1)
        ->and(HandleChange::first()->old_handle)->toBe('old-handle')
        ->and(HandleChange::first()->new_handle)->toBe('new-handle');
});

it('prevents handle change within 30 days', function () {
    $business = Business::factory()->create(['handle' => 'current']);
    $user = $business->owner;

    HandleChange::create([
        'business_id' => $business->id,
        'old_handle' => 'previous',
        'new_handle' => 'current',
        'changed_by_user_id' => $user->id,
        'changed_at' => now()->subDays(10),
    ]);

    $service = new HandleService;

    expect(fn () => $service->changeHandle($business, 'another', $user))
        ->toThrow(RuntimeException::class, 'once every 30 days');
});

it('suggests alternative handles', function () {
    $service = new HandleService;
    $suggestions = $service->suggestAlternatives('muddy-paws');

    expect($suggestions)->toBeArray()
        ->and(count($suggestions))->toBe(5);
});
