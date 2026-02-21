<?php

use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::statamic('/knowledge-base', 'knowledge_base.index', [
    'title' => 'Knowledge Base',
    'layout' => 'layout',
])->middleware(['auth', EnsureSuperAdmin::class]);

Route::statamic('/knowledge-base/{slug}', 'knowledge_base.show', [
    'layout' => 'layout',
])->middleware(['auth', EnsureSuperAdmin::class]);
