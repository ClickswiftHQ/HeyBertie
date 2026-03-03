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

// Knowledge Base (admin only)
Route::statamic('/knowledge-base', 'knowledge_base.index', [
    'title' => 'Knowledge Base',
    'layout' => 'layout',
])->middleware(['auth', EnsureSuperAdmin::class]);

Route::statamic('/knowledge-base/{slug}', 'knowledge_base.show', [
    'layout' => 'layout',
])->middleware(['auth', EnsureSuperAdmin::class]);
