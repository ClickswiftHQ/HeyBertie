<?php

use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

// Blog (public)
Route::statamic('/blog', 'blog.index', [
    'title' => 'Blog',
    'layout' => false,
]);

// Guides (public)
Route::statamic('/guides', 'guides.index', [
    'title' => 'Guides',
    'layout' => false,
]);

// Help Centre (public)
Route::statamic('/help', 'help.index', [
    'title' => 'Help Centre',
    'layout' => false,
]);

// Docs (super admin only)
Route::statamic('/docs', 'docs.index', [
    'title' => 'Docs',
    'layout' => false,
])->middleware(['auth', EnsureSuperAdmin::class]);
