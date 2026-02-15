<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::view('/', 'marketing.home')->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/statamic.php';
