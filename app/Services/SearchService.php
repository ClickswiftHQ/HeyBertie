<?php

namespace App\Services;

use App\Models\GeocodeCache;
use App\Models\Location;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /** @var array<string, string> */
    private const SERVICES = [
        'dog-grooming' => 'Dog Grooming',
        'dog-walking' => 'Dog Walking',
        'cat-sitting' => 'Cat Sitting',
    ];

    /**
     * Search for locations near the given coordinates.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Location>
     */
    public function search(
        float $latitude,
        float $longitude,
        array $filters = [],
        int $perPage = 12,
    ): LengthAwarePaginator {
        $distance = (int) ($filters['distance'] ?? 25);
        $sort = $filters['sort'] ?? 'distance';
        $type = $filters['type'] ?? null;
        $rating = isset($filters['rating']) ? (int) $filters['rating'] : null;

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return $this->searchWithPhp($latitude, $longitude, $distance, $sort, $type, $rating, $perPage);
        }

        return $this->searchWithSql($latitude, $longitude, $distance, $sort, $type, $rating, $perPage);
    }

    /**
     * Resolve a location slug to coordinates.
     *
     * @return array{latitude: float, longitude: float, name: string}|null
     */
    public function resolveLocation(string $slug): ?array
    {
        $cached = GeocodeCache::where('slug', $slug)->first();

        if (! $cached) {
            return null;
        }

        return [
            'name' => $cached->display_name,
            'latitude' => $cached->latitude,
            'longitude' => $cached->longitude,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function serviceNames(): array
    {
        return self::SERVICES;
    }

    /**
     * MySQL/PostgreSQL path: Haversine in SQL.
     *
     * @return LengthAwarePaginator<int, Location>
     */
    private function searchWithSql(
        float $latitude,
        float $longitude,
        int $distance,
        string $sort,
        ?string $type,
        ?int $rating,
        int $perPage,
    ): LengthAwarePaginator {
        $query = Location::query()
            ->selectRaw('locations.*, (6371 * acos(cos(radians(?)) * cos(radians(locations.latitude)) * cos(radians(locations.longitude) - radians(?)) + sin(radians(?)) * sin(radians(locations.latitude)))) AS distance', [
                $latitude, $longitude, $latitude,
            ])
            ->join('businesses', 'businesses.id', '=', 'locations.business_id')
            ->where('businesses.is_active', true)
            ->where('businesses.onboarding_completed', true)
            ->where('locations.is_active', true)
            ->whereNotNull('locations.latitude')
            ->whereNotNull('locations.longitude');

        $this->applyBoundingBox($query, $latitude, $longitude, $distance);

        if ($type) {
            $query->where('locations.location_type', $type);
        }

        if ($rating) {
            $query->whereExists(function ($sub) use ($rating) {
                $sub->selectRaw('1')
                    ->from('reviews')
                    ->whereColumn('reviews.business_id', 'businesses.id')
                    ->where('reviews.is_published', true)
                    ->groupBy('reviews.business_id')
                    ->havingRaw('AVG(reviews.rating) >= ?', [$rating]);
            });
        }

        $query->having('distance', '<=', $distance);

        $this->applySorting($query, $sort);

        $query->with([
            'business.subscriptionTier',
            'business.reviews' => fn ($q) => $q->where('is_published', true),
            'services' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
        ]);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * SQLite path: bounding-box pre-filter + PHP-level Haversine.
     *
     * @return LengthAwarePaginator<int, Location>
     */
    private function searchWithPhp(
        float $latitude,
        float $longitude,
        int $distance,
        string $sort,
        ?string $type,
        ?int $rating,
        int $perPage,
    ): LengthAwarePaginator {
        $query = Location::query()
            ->join('businesses', 'businesses.id', '=', 'locations.business_id')
            ->where('businesses.is_active', true)
            ->where('businesses.onboarding_completed', true)
            ->where('locations.is_active', true)
            ->whereNotNull('locations.latitude')
            ->whereNotNull('locations.longitude')
            ->select('locations.*');

        $this->applyBoundingBox($query, $latitude, $longitude, $distance);

        if ($type) {
            $query->where('locations.location_type', $type);
        }

        if ($rating) {
            $query->whereExists(function ($sub) use ($rating) {
                $sub->selectRaw('1')
                    ->from('reviews')
                    ->whereColumn('reviews.business_id', 'businesses.id')
                    ->where('reviews.is_published', true)
                    ->groupBy('reviews.business_id')
                    ->havingRaw('AVG(reviews.rating) >= ?', [$rating]);
            });
        }

        $query->with([
            'business.subscriptionTier',
            'business.reviews' => fn ($q) => $q->where('is_published', true),
            'services' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
        ]);

        $results = $query->get();

        // Calculate distance in PHP and filter
        $results = $results->map(function (Location $location) use ($latitude, $longitude) {
            $location->setAttribute('distance', $location->getDistanceFrom($latitude, $longitude));

            return $location;
        })->filter(fn (Location $location) => $location->distance <= $distance);

        // Sort
        $results = match ($sort) {
            'rating' => $results->sortByDesc(fn (Location $loc) => $loc->business->getAverageRating() ?? 0),
            'price_low' => $results->sortBy(fn (Location $loc) => $loc->services->where('price', '>', 0)->min('price') ?? PHP_FLOAT_MAX),
            'price_high' => $results->sortByDesc(fn (Location $loc) => $loc->services->where('price', '>', 0)->max('price') ?? 0),
            default => $results->sortBy('distance'),
        };

        $results = $results->values();
        $page = (int) request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $results->slice($offset, $perPage)->values(),
            $results->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * Apply a bounding box pre-filter to limit the search area.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Location>  $query
     */
    private function applyBoundingBox(mixed $query, float $latitude, float $longitude, int $distance): void
    {
        // ~1 degree latitude = 111km, ~1 degree longitude = 111km * cos(lat)
        $latDelta = $distance / 111.0;
        $lngDelta = $distance / (111.0 * cos(deg2rad($latitude)));

        $query->whereBetween('locations.latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('locations.longitude', [$longitude - $lngDelta, $longitude + $lngDelta]);
    }

    /**
     * Apply sorting to the SQL query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Location>  $query
     */
    private function applySorting(mixed $query, string $sort): void
    {
        match ($sort) {
            'rating' => $query->orderByDesc(
                DB::raw('(SELECT AVG(reviews.rating) FROM reviews WHERE reviews.business_id = businesses.id AND reviews.is_published = 1)')
            ),
            'price_low' => $query->orderBy(
                DB::raw('(SELECT MIN(services.price) FROM services WHERE services.business_id = businesses.id AND services.is_active = 1 AND services.price > 0)')
            ),
            'price_high' => $query->orderByDesc(
                DB::raw('(SELECT MAX(services.price) FROM services WHERE services.business_id = businesses.id AND services.is_active = 1 AND services.price > 0)')
            ),
            default => $query->orderBy('distance'),
        };
    }
}
