<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Location;
use Illuminate\Support\Collection;

class SchemaMarkupService
{
    /**
     * Generate Organization JSON-LD schema for a multi-location hub page.
     */
    public function generateForHub(Business $business): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $business->name,
            'url' => route('business.show', $business->handle),
            'description' => $business->description,
        ];

        if ($business->logo_url) {
            $schema['image'] = $business->logo_url;
        }

        $schema['subOrganization'] = $business->locations->map(fn (Location $loc) => [
            '@type' => 'LocalBusiness',
            'name' => $business->name.' — '.$loc->name,
            'url' => route('business.location', [$business->handle, $loc->slug]),
        ])->values()->all();

        return $schema;
    }

    /**
     * Generate complete JSON-LD schema for a business listing.
     *
     * @param  Collection<int, \App\Models\Service>  $services
     */
    public function generateForListing(
        Business $business,
        Location $location,
        Collection $services,
        ?float $avgRating,
        int $reviewCount,
        bool $isMultiLocation = false,
    ): array {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $business->name,
            'url' => $isMultiLocation
                ? route('business.location', [$business->handle, $location->slug])
                : route('business.show', $business->handle),
            'description' => $business->description,
        ];

        if ($business->logo_url) {
            $schema['image'] = $business->logo_url;
        }

        if ($business->phone) {
            $schema['telephone'] = $business->phone;
        }

        if ($business->email) {
            $schema['email'] = $business->email;
        }

        $schema['address'] = $this->generateAddress($location);

        if ($location->latitude && $location->longitude) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
            ];
        }

        if ($avgRating !== null && $reviewCount > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($avgRating, 1),
                'reviewCount' => (string) $reviewCount,
            ];
        }

        if ($location->opening_hours) {
            $schema['openingHoursSpecification'] = $this->generateOpeningHours($location->opening_hours);
        }

        if ($services->isNotEmpty()) {
            $schema['hasOfferCatalog'] = $this->generateServiceCatalog($services);
        }

        if ($isMultiLocation) {
            $schema['branchOf'] = [
                '@type' => 'Organization',
                'name' => $business->name,
                'url' => route('business.show', $business->handle),
            ];
        }

        return $schema;
    }

    /**
     * Convert schema array to JSON-LD string for embedding in HTML.
     */
    public function toJsonLd(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, string>
     */
    private function generateAddress(Location $location): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $location->address_line_1,
            'addressLocality' => $location->city,
            'addressRegion' => $location->county,
            'postalCode' => $location->postcode,
            'addressCountry' => 'GB',
        ]);
    }

    /**
     * @param  array<string, array{open: string, close: string}|null>  $hours
     * @return array<int, array<string, string>>
     */
    private function generateOpeningHours(array $hours): array
    {
        $dayMap = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];

        $specs = [];

        foreach ($hours as $day => $times) {
            if ($times === null) {
                continue;
            }

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $dayMap[$day] ?? ucfirst($day),
                'opens' => $times['open'],
                'closes' => $times['close'],
            ];
        }

        return $specs;
    }

    /**
     * @param  Collection<int, \App\Models\Service>  $services
     */
    private function generateServiceCatalog(Collection $services): array
    {
        return [
            '@type' => 'OfferCatalog',
            'name' => 'Grooming Services',
            'itemListElement' => $services->map(fn ($service) => array_filter([
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $service->name,
                    'description' => $service->description,
                ],
                'price' => $service->price ? number_format((float) $service->price, 2, '.', '') : null,
                'priceCurrency' => $service->price ? 'GBP' : null,
            ]))->values()->all(),
        ];
    }
}
