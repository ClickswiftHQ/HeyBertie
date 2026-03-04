<?php

use App\Models\User;

test('guests are redirected to login from docs index', function () {
    $this->get('/docs')
        ->assertRedirect('/login');
});

test('regular users are forbidden from docs index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/docs')
        ->assertForbidden();
});

test('super admins can access docs index', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get('/docs')
        ->assertOk();
});

test('guests are redirected to login from docs entry', function () {
    $this->get('/docs/getting-started')
        ->assertRedirectContains('/login');
});

test('regular users are forbidden from docs entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/docs/getting-started')
        ->assertForbidden();
});

test('super admins can access docs entry', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get('/docs/getting-started')
        ->assertOk();
});
