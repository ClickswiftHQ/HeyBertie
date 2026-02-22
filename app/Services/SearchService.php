<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /** @var array<string, array{name: string, latitude: float, longitude: float}> */
    private const LOCATIONS = [
        // Major cities
        'london' => ['name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278],
        'manchester' => ['name' => 'Manchester', 'latitude' => 53.4808, 'longitude' => -2.2426],
        'birmingham' => ['name' => 'Birmingham', 'latitude' => 52.4862, 'longitude' => -1.8904],
        'leeds' => ['name' => 'Leeds', 'latitude' => 53.8008, 'longitude' => -1.5491],
        'bristol' => ['name' => 'Bristol', 'latitude' => 51.4545, 'longitude' => -2.5879],
        'liverpool' => ['name' => 'Liverpool', 'latitude' => 53.4084, 'longitude' => -2.9916],
        'edinburgh' => ['name' => 'Edinburgh', 'latitude' => 55.9533, 'longitude' => -3.1883],
        'glasgow' => ['name' => 'Glasgow', 'latitude' => 55.8642, 'longitude' => -4.2518],
        'sheffield' => ['name' => 'Sheffield', 'latitude' => 53.3811, 'longitude' => -1.4701],
        'cardiff' => ['name' => 'Cardiff', 'latitude' => 51.4816, 'longitude' => -3.1791],
        'nottingham' => ['name' => 'Nottingham', 'latitude' => 52.9548, 'longitude' => -1.1581],
        'newcastle' => ['name' => 'Newcastle', 'latitude' => 54.9783, 'longitude' => -1.6178],
        'brighton' => ['name' => 'Brighton', 'latitude' => 50.8225, 'longitude' => -0.1372],
        'cambridge' => ['name' => 'Cambridge', 'latitude' => 52.2053, 'longitude' => 0.1218],
        'oxford' => ['name' => 'Oxford', 'latitude' => 51.7520, 'longitude' => -1.2577],
        'bath' => ['name' => 'Bath', 'latitude' => 51.3811, 'longitude' => -2.3590],
        'york' => ['name' => 'York', 'latitude' => 53.9591, 'longitude' => -1.0815],
        'reading' => ['name' => 'Reading', 'latitude' => 51.4543, 'longitude' => -0.9781],
        'southampton' => ['name' => 'Southampton', 'latitude' => 50.9097, 'longitude' => -1.4044],
        'belfast' => ['name' => 'Belfast', 'latitude' => 54.5973, 'longitude' => -5.9301],

        // London towns/areas
        'fulham-london' => ['name' => 'Fulham, London', 'latitude' => 51.4749, 'longitude' => -0.2010],
        'chelsea-london' => ['name' => 'Chelsea, London', 'latitude' => 51.4875, 'longitude' => -0.1687],
        'camden-london' => ['name' => 'Camden, London', 'latitude' => 51.5390, 'longitude' => -0.1426],
        'islington-london' => ['name' => 'Islington, London', 'latitude' => 51.5362, 'longitude' => -0.1033],
        'hackney-london' => ['name' => 'Hackney, London', 'latitude' => 51.5450, 'longitude' => -0.0553],
        'clapham-london' => ['name' => 'Clapham, London', 'latitude' => 51.4620, 'longitude' => -0.1380],
        'brixton-london' => ['name' => 'Brixton, London', 'latitude' => 51.4613, 'longitude' => -0.1156],
        'wimbledon-london' => ['name' => 'Wimbledon, London', 'latitude' => 51.4214, 'longitude' => -0.2064],
        'greenwich-london' => ['name' => 'Greenwich, London', 'latitude' => 51.4769, 'longitude' => -0.0005],
        'richmond-london' => ['name' => 'Richmond, London', 'latitude' => 51.4613, 'longitude' => -0.3037],
    ];

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
        return self::LOCATIONS[$slug] ?? null;
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
