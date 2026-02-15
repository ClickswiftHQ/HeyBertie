<?php

use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::statamic('/knowledge-base', 'knowledge_base.index', [
    'title' => 'Knowledge Base',
    'layout' => 'layout',
])->middleware(['auth', EnsureSuperAdmin::class]);
