<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\PageViewService;
use App\Services\SchemaMarkupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function __construct(
        private PageViewService $pageViewService,
        private SchemaMarkupService $schemaMarkupService,
    ) {}

    public function show(string $handle): View
    {
        $business = $this->loadBusiness($handle);

        if ($business->locations->count() > 1) {
            return $this->renderHub($business);
        }

        $location = $business->locations->first();

        return $this->renderListing($business, $location);
    }

    public function showLocation(string $handle, string $locationSlug): View
    {
        $business = $this->loadBusiness($handle);
        $location = $business->locations->firstWhere('slug', $locationSlug);

        abort_if(! $location, 404);

        return $this->renderListing($business, $location);
    }

    public function showCanonical(string $slug, int $id): RedirectResponse
    {
        $business = Business::query()
            ->where('is_active', true)
            ->where('onboarding_completed', true)
            ->findOrFail($id);

        return redirect()->route('business.show', $business->handle, 301);
    }

    private function loadBusiness(string $handle): Business
    {
        return Business::query()
            ->where('handle', $handle)
            ->where('is_active', true)
            ->where('onboarding_completed', true)
            ->with([
                'locations' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_primary'),
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
                'reviews' => fn ($q) => $q
                    ->where('is_published', true)
                    ->with('user:id,name')
                    ->latest()
                    ->limit(10),
                'subscriptionTier:id,slug',
            ])
            ->firstOrFail();
    }

    private function renderHub(Business $business): View
    {
        $avgRating = $business->getAverageRating();
        $reviewCount = $business->getReviewCount();

        $schemaMarkup = $this->schemaMarkupService->toJsonLd(
            $this->schemaMarkupService->generateForHub($business)
        );

        return view('listing.hub', [
            'business' => $business,
            'locations' => $business->locations,
            'rating' => [
                'average' => $avgRating ? round($avgRating, 1) : 0,
                'count' => $reviewCount,
            ],
            'schemaMarkup' => $schemaMarkup,
            'canonicalUrl' => route('business.show', $business->handle),
        ]);
    }

    private function renderListing(Business $business, ?object $location): View
    {
        abort_if(! $location, 404);

        $isMultiLocation = $business->locations->count() > 1;

        $services = $location
            ? $business->services->filter(fn ($s) => $s->location_id === null || $s->location_id === $location->id)->values()
            : $business->services;

        $avgRating = $business->getAverageRating();
        $reviewCount = $business->getReviewCount();
        $breakdown = $business->getRatingBreakdown();

        $schemaMarkup = $this->schemaMarkupService->toJsonLd(
            $this->schemaMarkupService->generateForListing(
                $business,
                $location,
                $services,
                $avgRating,
                $reviewCount,
                $isMultiLocation,
            )
        );

        $this->pageViewService->trackView($business, $location, request());

        $tierSlug = $business->subscriptionTier->slug ?? 'free';

        $canonicalUrl = $location->is_primary && ! $isMultiLocation
            ? route('business.show', $business->handle)
            : route('business.location', [$business->handle, $location->slug]);

        return view('listing.show', [
            'business' => $business,
            'location' => $location,
            'locations' => $business->locations,
            'services' => $services,
            'rating' => [
                'average' => $avgRating ? round($avgRating, 1) : 0,
                'count' => $reviewCount,
                'breakdown' => $breakdown,
            ],
            'reviews' => $business->reviews,
            'hasMoreReviews' => $reviewCount > 10,
            'canBook' => $tierSlug !== 'free',
            'schemaMarkup' => $schemaMarkup,
            'canonicalUrl' => $canonicalUrl,
        ]);
    }
}
