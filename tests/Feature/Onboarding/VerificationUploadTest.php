<?php

use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    SubscriptionTier::firstOrCreate(['slug' => 'free'], ['name' => 'Free', 'monthly_price_pence' => 0, 'sort_order' => 1]);
    SubscriptionStatus::firstOrCreate(['slug' => 'trial'], ['name' => 'Trial', 'sort_order' => 1]);

    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($this->user);

    // Complete steps 1-5 to access step 6
    $this->post(route('onboarding.store', 1), ['business_type' => 'salon']);
    $this->post(route('onboarding.store', 2), ['name' => 'Test Business']);
    $this->post(route('onboarding.store', 3), ['handle' => 'test-verify']);
    $this->post(route('onboarding.store', 4), [
        'address_line_1' => '1 Street',
        'town' => 'Fulham',
        'city' => 'London',
        'postcode' => 'SW1A 1AA',
    ]);
    $this->post(route('onboarding.store', 5), [
        'services' => [['name' => 'Groom', 'description' => '', 'duration_minutes' => 60, 'price' => 30, 'price_type' => 'fixed']],
    ]);
});

test('valid photo ID upload succeeds', function () {
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('passport.jpg', 400, 300)->size(1000),
    ])->assertRedirect(route('onboarding.step', 7));

    expect(VerificationDocument::count())->toBe(1)
        ->and(VerificationDocument::first()->document_type)->toBe('photo_id')
        ->and(VerificationDocument::first()->status)->toBe('pending');
});

test('invalid file type is rejected', function () {
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
    ])->assertSessionHasErrors('photo_id');
});

test('file too large is rejected', function () {
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('large.jpg')->size(6000),
    ])->assertSessionHasErrors('photo_id');
});

test('files are stored in private disk', function () {
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('passport.jpg', 400, 300)->size(500),
    ]);

    $document = VerificationDocument::first();
    Storage::disk('local')->assertExists($document->file_path);
});

test('verification document record is created with metadata', function () {
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('my-passport.jpg', 400, 300)->size(800),
    ]);

    $document = VerificationDocument::first();

    expect($document)->not->toBeNull()
        ->and($document->original_filename)->toBe('my-passport.jpg')
        ->and($document->mime_type)->toBe('image/jpeg')
        ->and($document->file_size)->toBeGreaterThan(0);
});

test('optional documents can be uploaded alongside photo ID', function () {
    $this->post(route('onboarding.store', 6), [
        'photo_id' => UploadedFile::fake()->image('passport.jpg')->size(500),
        'qualification' => UploadedFile::fake()->create('cert.pdf', 500, 'application/pdf'),
        'insurance' => UploadedFile::fake()->create('insurance.pdf', 500, 'application/pdf'),
    ])->assertRedirect(route('onboarding.step', 7));

    expect(VerificationDocument::count())->toBe(3);
});

test('step can be skipped without uploading any documents', function () {
    $this->post(route('onboarding.store', 6), [])
        ->assertRedirect(route('onboarding.step', 7));

    expect(VerificationDocument::count())->toBe(0);
});
