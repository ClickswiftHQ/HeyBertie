<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PostcodeLookupController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchSuggestController;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::view('/', 'marketing.home')->name('home');

Route::get('for-dog-groomers', [MarketingController::class, 'forDogGroomers'])
    ->name('marketing.for-dog-groomers');

Route::get('join', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('onboarding.index');
    }

    $request->session()->put('registration_intent', 'business');

    return Inertia::render('auth/register', [
        'intent' => 'business',
    ]);
})->name('join');

// Dashboard redirect — resolves to user's primary business dashboard
Route::get('dashboard', function (Request $request) {
    $business = Business::query()
        ->where('owner_user_id', $request->user()->id)
        ->where('onboarding_completed', true)
        ->where('is_active', true)
        ->orderByDesc('created_at')
        ->first();

    if (! $business) {
        return redirect()->route('onboarding.index');
    }

    return redirect()->route('business.dashboard', $business->handle);
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

// Postcode lookup API
Route::get('/api/postcode-lookup/{postcode}', PostcodeLookupController::class)
    ->name('postcode.lookup')
    ->middleware('throttle:60,1');

// Search suggest API
Route::get('/api/search-suggest', SearchSuggestController::class)
    ->name('search.suggest')
    ->middleware('throttle:120,1');

// Search routes
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/{slug}', [SearchController::class, 'landing'])
    ->where('slug', '[a-z-]+-in-[a-z-]+')
    ->name('search.landing');

// Business management routes (authenticated, handle-scoped)
Route::middleware(['auth', 'verified', 'onboarding.complete', 'business.manage'])
    ->where(['handle' => '[a-z0-9][a-z0-9-]*'])
    ->group(function () {
        Route::get('/{handle}/dashboard', DashboardController::class)
            ->name('business.dashboard');
    });

// Vanity handle routes — MUST be LAST (catch-all-like pattern)
Route::middleware('handle.redirect')->group(function () {
    Route::get('/{handle}', [BusinessController::class, 'show'])
        ->name('business.show')
        ->where('handle', '[a-z0-9][a-z0-9-]*');
    Route::get('/{handle}/{locationSlug}', [BusinessController::class, 'showLocation'])
        ->name('business.location')
        ->where('handle', '[a-z0-9][a-z0-9-]*');
});
