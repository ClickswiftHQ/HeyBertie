<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Location;
use App\Models\Service;
use App\Models\SubscriptionStatus;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardingService
{
    public const TOTAL_STEPS = 7;

    /**
     * @var list<array{name: string, typical_duration: int, typical_price: float}>
     */
    private const SUGGESTED_SERVICES = [
        ['name' => 'Full Groom', 'typical_duration' => 90, 'typical_price' => 45.00],
        ['name' => 'Bath & Brush', 'typical_duration' => 60, 'typical_price' => 30.00],
        ['name' => 'Puppy First Groom', 'typical_duration' => 45, 'typical_price' => 25.00],
        ['name' => 'Nail Trim', 'typical_duration' => 15, 'typical_price' => 10.00],
        ['name' => 'Teeth Cleaning', 'typical_duration' => 30, 'typical_price' => 20.00],
        ['name' => 'De-matting', 'typical_duration' => 60, 'typical_price' => 35.00],
        ['name' => 'Hand Stripping', 'typical_duration' => 120, 'typical_price' => 60.00],
    ];

    public function __construct(
        private HandleService $handleService,
        private GeocodingService $geocodingService,
    ) {}

    public function getDraftBusiness(User $user): ?Business
    {
        return Business::query()
            ->where('owner_user_id', $user->id)
            ->where('onboarding_completed', false)
            ->first();
    }

    public function createDraft(User $user): Business
    {
        $tier = SubscriptionTier::where('slug', 'free')->firstOrFail();
        $status = SubscriptionStatus::where('slug', 'trial')->firstOrFail();

        $uniqueDraftSuffix = Str::random(12);

        return Business::create([
            'name' => '',
            'handle' => "draft-{$uniqueDraftSuffix}",
            'slug' => "draft-{$uniqueDraftSuffix}",
            'owner_user_id' => $user->id,
            'subscription_tier_id' => $tier->id,
            'subscription_status_id' => $status->id,
            'verification_status' => 'pending',
            'is_active' => false,
            'onboarding' => [
                'current_step' => 1,
                'completed_steps' => [],
                'business_type' => null,
                'started_at' => now()->toISOString(),
                'completed_at' => null,
            ],
            'onboarding_completed' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveStep(Business $business, int $step, array $data): void
    {
        match ($step) {
            1 => $this->saveBusinessType($business, $data),
            2 => $this->saveBusinessDetails($business, $data),
            3 => $this->saveHandle($business, $data),
            4 => $this->saveLocation($business, $data),
            5 => $this->saveServices($business, $data),
            6 => $this->saveVerification($business, $data),
            7 => $this->savePlanSelection($business, $data),
            default => throw new \InvalidArgumentException("Invalid step: {$step}"),
        };

        // Re-read onboarding after step-specific save may have modified it
        $business->refresh();
        $onboarding = $business->onboarding ?? [];
        $completedSteps = $onboarding['completed_steps'] ?? [];

        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            sort($completedSteps);
        }

        $nextStep = min($step + 1, self::TOTAL_STEPS + 1);

        $business->update([
            'onboarding' => array_merge($onboarding, [
                'current_step' => max($onboarding['current_step'] ?? 1, $nextStep),
                'completed_steps' => $completedSteps,
            ]),
        ]);
    }

    public function getCurrentStep(Business $business): int
    {
        $onboarding = $business->onboarding ?? [];
        $completedSteps = $onboarding['completed_steps'] ?? [];

        for ($i = 1; $i <= self::TOTAL_STEPS; $i++) {
            if (! in_array($i, $completedSteps)) {
                return $i;
            }
        }

        return self::TOTAL_STEPS + 1;
    }

    public function canAccessStep(Business $business, int $step): bool
    {
        if ($step === 1) {
            return true;
        }

        $onboarding = $business->onboarding ?? [];
        $completedSteps = $onboarding['completed_steps'] ?? [];

        for ($i = 1; $i < $step; $i++) {
            if (! in_array($i, $completedSteps)) {
                return false;
            }
        }

        return true;
    }

    public function finalize(Business $business): void
    {
        DB::transaction(function () use ($business) {
            $onboarding = $business->onboarding ?? [];

            $tier = SubscriptionTier::where('slug', $onboarding['selected_tier'] ?? 'free')->firstOrFail();
            $trialStatus = SubscriptionStatus::where('slug', 'trial')->firstOrFail();
            $ownerRole = BusinessRole::where('slug', 'owner')->firstOrFail();

            // Create location from saved onboarding data
            $locationData = $onboarding['location'] ?? [];
            $businessType = $onboarding['business_type'] ?? 'salon';

            $address = implode(', ', array_filter([
                $locationData['address_line_1'] ?? '',
                $locationData['city'] ?? '',
                $locationData['postcode'] ?? '',
            ]));

            $coords = $this->geocodingService->geocode($address);

            $location = Location::create([
                'business_id' => $business->id,
                'name' => $locationData['name'] ?? $business->name,
                'slug' => Location::generateSlug($locationData['town'] ?? '', $locationData['city'] ?? ''),
                'location_type' => $locationData['location_type'] ?? $businessType,
                'address_line_1' => $locationData['address_line_1'] ?? '',
                'address_line_2' => $locationData['address_line_2'] ?? null,
                'town' => $locationData['town'] ?? '',
                'city' => $locationData['city'] ?? '',
                'postcode' => $locationData['postcode'] ?? '',
                'county' => $locationData['county'] ?? null,
                'latitude' => $coords['latitude'] ?? null,
                'longitude' => $coords['longitude'] ?? null,
                'is_mobile' => in_array($businessType, ['mobile', 'hybrid']),
                'service_radius_km' => $locationData['service_radius_km'] ?? null,
                'phone' => $locationData['phone'] ?? null,
                'email' => $locationData['email'] ?? null,
                'is_primary' => true,
                'is_active' => true,
                'accepts_bookings' => true,
            ]);

            // Create services from saved onboarding data
            $services = $onboarding['services'] ?? [];
            foreach ($services as $index => $serviceData) {
                Service::create([
                    'business_id' => $business->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'] ?? null,
                    'duration_minutes' => $serviceData['duration_minutes'],
                    'price' => $serviceData['price'] ?? null,
                    'price_type' => $serviceData['price_type'] ?? 'fixed',
                    'display_order' => $index,
                    'is_active' => true,
                    'is_featured' => false,
                ]);
            }

            // Create business_user pivot (owner role)
            $business->users()->syncWithoutDetaching([
                $business->owner_user_id => [
                    'business_role_id' => $ownerRole->id,
                    'is_active' => true,
                    'accepted_at' => now(),
                ],
            ]);

            // Update business
            $business->update([
                'subscription_tier_id' => $tier->id,
                'subscription_status_id' => $trialStatus->id,
                'trial_ends_at' => $tier->trial_days > 0 ? now()->addDays($tier->trial_days) : null,
                'is_active' => true,
                'onboarding_completed' => true,
                'onboarding' => array_merge($onboarding, [
                    'completed_at' => now()->toISOString(),
                ]),
            ]);

            // Update user role to pro
            User::where('id', $business->owner_user_id)->update(['role' => 'pro']);
        });
    }

    /**
     * @return list<array{name: string, typical_duration: int, typical_price: float}>
     */
    public function getSuggestedServices(string $businessType): array
    {
        return self::SUGGESTED_SERVICES;
    }

    /**
     * @return list<string>
     */
    public function suggestHandles(string $businessName): array
    {
        $base = Str::slug($businessName);

        if (strlen($base) < 3) {
            return [];
        }

        $base = Str::limit($base, 30, '');

        return $this->handleService->suggestAlternatives($base);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveBusinessType(Business $business, array $data): void
    {
        $onboarding = $business->onboarding ?? [];
        $onboarding['business_type'] = $data['business_type'];

        $business->update(['onboarding' => $onboarding]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveBusinessDetails(Business $business, array $data): void
    {
        $updates = [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
        ];

        if (isset($data['logo']) && $data['logo']) {
            $path = $data['logo']->store('logos', 'public');
            $updates['logo_url'] = $path;
        }

        $business->update($updates);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveHandle(Business $business, array $data): void
    {
        $business->update(['handle' => $data['handle']]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveLocation(Business $business, array $data): void
    {
        $onboarding = $business->onboarding ?? [];
        $onboarding['location'] = [
            'name' => $data['name'] ?? $business->name,
            'location_type' => $data['location_type'] ?? $onboarding['business_type'] ?? 'salon',
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'town' => $data['town'],
            'city' => $data['city'],
            'postcode' => $data['postcode'],
            'county' => $data['county'] ?? null,
            'service_radius_km' => $data['service_radius_km'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ];

        $business->update(['onboarding' => $onboarding]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveServices(Business $business, array $data): void
    {
        $onboarding = $business->onboarding ?? [];
        $onboarding['services'] = $data['services'];

        $business->update(['onboarding' => $onboarding]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveVerification(Business $business, array $data): void
    {
        $files = $data['files'] ?? [];

        foreach ($files as $type => $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store("verification/{$business->id}", 'local');

            // Remove existing document of same type
            $business->verificationDocuments()->where('document_type', $type)->delete();

            VerificationDocument::create([
                'business_id' => $business->id,
                'document_type' => $type,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'pending',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function savePlanSelection(Business $business, array $data): void
    {
        $onboarding = $business->onboarding ?? [];
        $onboarding['selected_tier'] = $data['tier'];

        $business->update(['onboarding' => $onboarding]);
    }
}
