<?php

namespace App\Http\Controllers;

use App\Http\Requests\Onboarding\StoreBusinessDetailsRequest;
use App\Http\Requests\Onboarding\StoreBusinessTypeRequest;
use App\Http\Requests\Onboarding\StoreHandleRequest;
use App\Http\Requests\Onboarding\StoreLocationRequest;
use App\Http\Requests\Onboarding\StorePlanSelectionRequest;
use App\Http\Requests\Onboarding\StoreServicesRequest;
use App\Http\Requests\Onboarding\StoreVerificationRequest;
use App\Models\Business;
use App\Models\SubscriptionTier;
use App\Rules\ValidHandle;
use App\Services\HandleService;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService,
        private HandleService $handleService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        // If user already has a completed business, go to dashboard
        $hasCompletedBusiness = Business::query()
            ->where('owner_user_id', $request->user()->id)
            ->where('onboarding_completed', true)
            ->exists();

        if ($hasCompletedBusiness) {
            return redirect()->route('dashboard');
        }

        $business = $this->getOrCreateDraft($request);

        $currentStep = $this->onboardingService->getCurrentStep($business);

        if ($currentStep > OnboardingService::TOTAL_STEPS) {
            return redirect()->route('onboarding.review');
        }

        return redirect()->route('onboarding.step', $currentStep);
    }

    public function show(Request $request, int $step): Response|RedirectResponse
    {
        if ($step < 1 || $step > OnboardingService::TOTAL_STEPS) {
            return redirect()->route('onboarding.index');
        }

        $business = $this->getOrCreateDraft($request);

        if ($business->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        if (! $this->onboardingService->canAccessStep($business, $step)) {
            $currentStep = $this->onboardingService->getCurrentStep($business);

            return redirect()->route('onboarding.step', $currentStep);
        }

        return $this->renderStep($business, $step);
    }

    public function store(Request $request, int $step): RedirectResponse
    {
        $business = $this->getOrCreateDraft($request);

        if ($business->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        if (! $this->onboardingService->canAccessStep($business, $step)) {
            return redirect()->route('onboarding.index');
        }

        $validated = $this->validateStep($request, $step);

        $this->onboardingService->saveStep($business, $step, $validated);

        if ($step >= OnboardingService::TOTAL_STEPS) {
            return redirect()->route('onboarding.review');
        }

        return redirect()->route('onboarding.step', $step + 1);
    }

    public function checkHandle(Request $request): JsonResponse
    {
        $handle = $request->input('handle', '');

        $validator = validator(['handle' => $handle], [
            'handle' => ['required', 'string', 'min:3', 'max:30', new ValidHandle],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'suggestions' => $this->handleService->suggestAlternatives($handle),
            ]);
        }

        return response()->json([
            'available' => true,
            'suggestions' => [],
        ]);
    }

    public function review(Request $request): Response|RedirectResponse
    {
        $business = $this->getOrCreateDraft($request);

        if ($business->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $currentStep = $this->onboardingService->getCurrentStep($business);

        if ($currentStep <= OnboardingService::TOTAL_STEPS) {
            return redirect()->route('onboarding.step', $currentStep);
        }

        $onboarding = $business->onboarding ?? [];
        $location = $onboarding['location'] ?? [];

        $tiers = SubscriptionTier::orderBy('sort_order')->get();
        $selectedTier = $tiers->firstWhere('slug', $onboarding['selected_tier'] ?? 'free');

        return Inertia::render('onboarding/shared/review', [
            'business' => [
                'type' => $onboarding['business_type'] ?? null,
                'name' => $business->name,
                'description' => $business->description,
                'handle' => $business->handle,
                'phone' => $business->phone,
                'email' => $business->email,
                'website' => $business->website,
                'logo_url' => $business->logo_url,
            ],
            'location' => [
                'name' => $location['name'] ?? '',
                'location_type' => $location['location_type'] ?? '',
                'address' => implode(', ', array_filter([
                    $location['address_line_1'] ?? '',
                    $location['address_line_2'] ?? '',
                    $location['town'] ?? '',
                    $location['city'] ?? '',
                    $location['postcode'] ?? '',
                ])),
                'service_radius_km' => $location['service_radius_km'] ?? null,
            ],
            'services' => collect($onboarding['services'] ?? [])->map(fn (array $s) => [
                'name' => $s['name'],
                'duration_minutes' => $s['duration_minutes'],
                'price' => $s['price'] ?? null,
                'price_type' => $s['price_type'],
            ])->all(),
            'verification' => [
                'documents_count' => $business->verificationDocuments()->count(),
                'has_photo_id' => $business->verificationDocuments()->where('document_type', 'photo_id')->exists(),
            ],
            'plan' => [
                'tier' => $selectedTier->slug ?? 'free',
                'name' => $selectedTier->name ?? 'Free',
                'price' => ($selectedTier->monthly_price_pence ?? 0) / 100,
            ],
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $business = $this->getOrCreateDraft($request);

        if ($business->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $this->onboardingService->finalize($business);
        $business->refresh();

        if ($business->subscriptionTier->stripe_price_id) {
            return redirect()->route('subscription.checkout', $business->handle);
        }

        return redirect()->route('business.dashboard', $business->handle)
            ->with('success', 'Your business has been created! Welcome to heyBertie.');
    }

    private function getOrCreateDraft(Request $request): Business
    {
        $user = $request->user();
        $business = $this->onboardingService->getDraftBusiness($user);

        if (! $business) {
            $business = $this->onboardingService->createDraft($user);
        }

        return $business;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStep(Request $request, int $step): array
    {
        return match ($step) {
            1 => app(StoreBusinessTypeRequest::class)->validated(),
            2 => app(StoreBusinessDetailsRequest::class)->validated(),
            3 => app(StoreHandleRequest::class)->validated(),
            4 => app(StoreLocationRequest::class)->validated(),
            5 => app(StoreServicesRequest::class)->validated(),
            6 => $this->validateVerificationStep($request),
            7 => app(StorePlanSelectionRequest::class)->validated(),
            default => throw new \InvalidArgumentException("Invalid step: {$step}"),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVerificationStep(Request $request): array
    {
        $validated = app(StoreVerificationRequest::class)->validated();

        return [
            'files' => [
                'photo_id' => $request->file('photo_id'),
                'qualification' => $request->file('qualification'),
                'insurance' => $request->file('insurance'),
            ],
        ];
    }

    private function renderStep(Business $business, int $step): Response
    {
        $onboarding = $business->onboarding ?? [];
        $completedSteps = $onboarding['completed_steps'] ?? [];

        $baseProps = [
            'step' => $step,
            'totalSteps' => OnboardingService::TOTAL_STEPS,
            'completedSteps' => $completedSteps,
        ];

        return match ($step) {
            1 => Inertia::render('onboarding/shared/step-1-business-type', array_merge($baseProps, [
                'businessType' => $onboarding['business_type'] ?? null,
            ])),
            2 => Inertia::render('onboarding/grooming/step-2-business-details', array_merge($baseProps, [
                'business' => [
                    'name' => $business->name ?: null,
                    'description' => $business->description,
                    'phone' => $business->phone,
                    'email' => $business->email,
                    'website' => $business->website,
                    'logo_url' => $business->logo_url,
                ],
            ])),
            3 => Inertia::render('onboarding/shared/step-3-handle', array_merge($baseProps, [
                'handle' => $business->handle ?: null,
                'suggestedHandles' => $business->name
                    ? $this->onboardingService->suggestHandles($business->name)
                    : [],
            ])),
            4 => Inertia::render('onboarding/grooming/step-4-location', array_merge($baseProps, [
                'businessType' => $onboarding['business_type'] ?? 'salon',
                'location' => $onboarding['location'] ?? [
                    'name' => null,
                    'location_type' => null,
                    'address_line_1' => null,
                    'address_line_2' => null,
                    'town' => null,
                    'city' => null,
                    'postcode' => null,
                    'county' => null,
                    'service_radius_km' => null,
                    'phone' => $business->phone,
                    'email' => $business->email,
                ],
            ])),
            5 => Inertia::render('onboarding/grooming/step-5-services', array_merge($baseProps, [
                'services' => $onboarding['services'] ?? [],
                'suggestedServices' => $this->onboardingService->getSuggestedServices(
                    $onboarding['business_type'] ?? 'salon'
                ),
            ])),
            6 => Inertia::render('onboarding/shared/step-6-verification', array_merge($baseProps, [
                'documents' => $business->verificationDocuments()
                    ->get(['id', 'document_type', 'original_filename', 'status'])
                    ->toArray(),
                'documentTypes' => ['photo_id', 'qualification', 'insurance'],
            ])),
            7 => Inertia::render('onboarding/shared/step-7-plan', array_merge($baseProps, [
                'selectedTier' => $onboarding['selected_tier'] ?? null,
                'plans' => $this->getPlans(),
            ])),
        };
    }

    /**
     * @return list<array{tier: string, name: string, price: float, trial_days: int, features: list<string>, highlighted: bool, cta: string}>
     */
    private function getPlans(): array
    {
        $featuresBySlug = [
            'free' => [
                'Business listing',
                'Handle URL (@name)',
            ],
            'solo' => [
                'Everything in Free',
                'Booking calendar',
                'Online payments',
                'CRM / customer notes',
                '30 SMS reminders/month',
                'Unlimited email reminders',
                'Basic analytics',
            ],
            'salon' => [
                'Everything in Solo',
                'Up to 5 staff calendars',
                'Up to 3 locations',
                '100 SMS reminders/month',
                'Loyalty program',
                'Advanced analytics',
                'Priority support',
            ],
        ];

        return SubscriptionTier::orderBy('sort_order')->get()->map(fn (SubscriptionTier $tier) => [
            'tier' => $tier->slug,
            'name' => $tier->name,
            'price' => $tier->monthly_price_pence / 100,
            'trial_days' => $tier->trial_days,
            'features' => $featuresBySlug[$tier->slug] ?? [],
            'highlighted' => $tier->slug === 'solo',
            'cta' => $tier->trial_days > 0
                ? "Start {$tier->trial_days}-day Trial"
                : 'Start Free',
        ])->all();
    }
}
