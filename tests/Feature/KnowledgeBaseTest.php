<?php

use App\Models\User;

test('guests are redirected to login from knowledge base index', function () {
    $this->get('/knowledge-base')
        ->assertRedirect('/login');
});

test('regular users are forbidden from knowledge base index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/knowledge-base')
        ->assertForbidden();
});

test('super admins can access knowledge base index', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get('/knowledge-base')
        ->assertOk();
});

test('guests are redirected to login from knowledge base entry', function () {
    $this->get('/knowledge-base/getting-started')
        ->assertRedirectContains('/login');
});

test('regular users are forbidden from knowledge base entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/knowledge-base/getting-started')
        ->assertForbidden();
});

test('super admins can access knowledge base entry', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get('/knowledge-base/getting-started')
        ->assertOk();
});
