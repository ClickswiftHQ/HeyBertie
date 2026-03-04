<?php

use App\Models\Business;
use App\Models\User;

test('guests are redirected to login from admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect(route('login'));
});

test('non-super users get 403 on admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('super admin can view admin dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/index')
            ->has('stats')
            ->has('stats.totalBusinesses')
            ->has('stats.mrr')
            ->has('stats.pendingVerifications')
            ->has('stats.subscriptionBreakdown')
            ->has('stats.expiringTrials')
            ->has('stats.recentSignups')
        );
});

test('admin stats include correct business counts', function () {
    $admin = User::factory()->superAdmin()->create();

    Business::factory()->solo()->completed()->verified()->create();
    Business::factory()->salon()->completed()->verified()->create();
    Business::factory()->completed()->create(); // pending verification

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('stats.totalBusinesses', 3)
            ->where('stats.verifiedBusinesses', 2)
            ->where('stats.pendingVerifications', 1)
        );
});

test('admin stats include MRR from active subscriptions', function () {
    $admin = User::factory()->superAdmin()->create();

    // Solo tier at £19.99 (1999 pence), active status
    Business::factory()->solo()->completed()->verified()->create();
    // Free tier — should not contribute to MRR
    Business::factory()->completed()->verified()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('stats.mrr', 19.99)
        );
});

test('admin stats include expiring trials', function () {
    $admin = User::factory()->superAdmin()->create();

    // Trial expiring tomorrow
    Business::factory()->solo()->completed()->create([
        'trial_ends_at' => now()->addDay(),
    ]);

    // Trial expiring in 10 days — should NOT appear (outside 3-day window)
    Business::factory()->solo()->completed()->create([
        'trial_ends_at' => now()->addDays(10),
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('stats.expiringTrials', 1)
        );
});

test('admin stats include recent signups', function () {
    $admin = User::factory()->superAdmin()->create();

    // Created today
    Business::factory()->completed()->create();
    // Created 10 days ago — should NOT appear
    Business::factory()->completed()->create(['created_at' => now()->subDays(10)]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('stats.recentSignups', 1)
        );
});

test('admin route is excluded from vanity handle catch-all', function () {
    // Ensure /admin doesn't try to resolve as a business handle
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('admin/index'));
});
