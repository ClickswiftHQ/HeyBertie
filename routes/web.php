<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::view('/', 'marketing.home')->name('home');

Route::get('join', function (\Illuminate\Http\Request $request) {
    if ($request->user()) {
        return redirect()->route('onboarding.index');
    }

    $request->session()->put('registration_intent', 'business');

    return Inertia::render('auth/register', [
        'intent' => 'business',
    ]);
})->name('join');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified', 'onboarding.complete'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    Route::get('/step/{step}', [OnboardingController::class, 'show'])->name('step')->whereNumber('step');
    Route::post('/step/{step}', [OnboardingController::class, 'store'])->name('store')->whereNumber('step');
    Route::post('/check-handle', [OnboardingController::class, 'checkHandle'])->name('check-handle')->middleware('throttle:30,1');
    Route::get('/review', [OnboardingController::class, 'review'])->name('review');
    Route::post('/submit', [OnboardingController::class, 'submit'])->name('submit');
});

// Canonical URL — 301 redirect to vanity handle URL (permanent link)
Route::get('/p/{slug}-{id}', [BusinessController::class, 'showCanonical'])
    ->name('business.canonical')
    ->where(['slug' => '[a-z0-9-]+', 'id' => '[0-9]+']);

// Legacy /@handle redirects (301 to new clean URLs)
Route::get('/@{handle}', fn (string $handle) => redirect(route('business.show', $handle), 301))
    ->where('handle', '[a-z0-9][a-z0-9-]*');
Route::get('/@{handle}/{locationSlug}', fn (string $handle, string $locationSlug) => redirect(route('business.location', [$handle, $locationSlug]), 301))
    ->where('handle', '[a-z0-9][a-z0-9-]*');

require __DIR__.'/settings.php';
require __DIR__.'/statamic.php';

// Vanity handle routes — MUST be LAST (catch-all-like pattern)
Route::middleware('handle.redirect')->group(function () {
    Route::get('/{handle}', [BusinessController::class, 'show'])
        ->name('business.show')
        ->where('handle', '[a-z0-9][a-z0-9-]*');
    Route::get('/{handle}/{locationSlug}', [BusinessController::class, 'showLocation'])
        ->name('business.location')
        ->where('handle', '[a-z0-9][a-z0-9-]*');
});
