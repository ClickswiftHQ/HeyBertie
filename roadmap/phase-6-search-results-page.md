# Phase 6: Search Results Page - Detailed Specification

## High-Level Description

This phase builds the public search results page — the core discovery mechanism for heyBertie. When a customer searches for "dog grooming in Fulham" (via the homepage form, a city quick link, or directly), they see a list of matching businesses sorted by proximity, with filters for refinement. These pages are SEO-critical: city-level landing pages (e.g. `/dog-grooming-in-london`) are the primary organic traffic source.

---

## URL Structure Decision: Competitive Research

Before finalising the URL structure, we analysed how established UK service marketplaces structure their search and city landing pages. The goal: ensure our URLs match how people actually search Google (e.g. "dog grooming in London") for maximum keyword density in the slug.

### Platforms Analysed

| Platform | URL Pattern | Example | SEO Strength |
|----------|------------|---------|--------------|
| **TrustATrader** | `/{service}-in-{city}` | `/plumbers-in-camberley` | **Best** — slug IS the search query |
| **Checkatrade** | `/Search/{Service}/in/{City}` | `/Search/Plumber/in/Camberley` | Weak — `/Search/` prefix wastes slug space |
| **Deliveroo** | `/{type}/{city}/{area}` | `/restaurants/camberley/camberley` | Weak — redundant city/area, no service keyword |
| **Bark** | `/en/gb/{service}/{county}/{city}/` | `/en/gb/pet-grooming/surrey/camberley/` | Good — adds county geo hierarchy |
| **Good Dog Guide** | `/{county}/{city}/{service}` | `/surrey/camberley/dog-grooming-groomers` | Good — geographic hierarchy first |
| **Yelp** | `/search?cflt={service}&find_loc={City}` | `/search?cflt=groomer&find_loc=Camberley` | Weakest — query params, not landing pages |

### Detailed Findings

**TrustATrader** (`trustatrader.com/plumbers-in-camberley`):
- Title: "Find the most trusted local Plumbers in Camberley \| TrustATrader"
- H1: "Plumbers in Camberley"
- Breadcrumbs: Home > Trades > Plumbers > Plumbers in Camberley
- Internal links follow same pattern: `/electric-shower-in-camberley`, `/plumbers-in-london`
- The slug literally matches the Google search query "plumbers in camberley" — maximum keyword match
- Pagination moves to query params: `/search?trade_name=Plumbers&location_str=Camberley&page=2`

**Checkatrade** (`checkatrade.com/Search/Plumber/in/Camberley`):
- Title: "Find 142+ Plumbers in Camberley \| A Job Done Right"
- Uses capitalised path segments (`/Search/`, `/Plumber/`)
- The `/Search/` prefix adds no SEO value — it's wasted URL depth
- Related sub-services follow same pattern: `/Search/Plumbing-Repairs/in/Camberley`

**Bark** (`bark.com/en/gb/pet-grooming/surrey/camberley/`):
- Adds county (`surrey`) for geographic hierarchy
- Good for crawl depth and geo-targeting, but longer URLs
- Language/country prefix (`/en/gb/`) needed for international platform — not relevant for UK-only heyBertie

**Deliveroo** (`deliveroo.co.uk/restaurants/camberley/camberley`):
- Duplicates city in both `/{city}/{area}` slots when area = city
- No service keyword in URL (just "restaurants" generic category)
- Weakest keyword targeting of all platforms analysed

### Decision: `/{service}-in-{city}` Pattern

We adopted the TrustATrader pattern because:

1. **Slug = search query.** When someone Googles "dog grooming in london", our URL `/dog-grooming-in-london` is an exact keyword match. This is the strongest signal for organic ranking.
2. **Flat structure.** No wasted path segments (`/search/`, `/en/gb/`). Every character in the slug carries keyword weight.
3. **Proven at scale.** TrustATrader ranks consistently on page 1 for "{trade} in {city}" queries across hundreds of UK cities.
4. **Naturally extensible.** Town-level pages (`/dog-grooming-in-fulham-london`) follow the same pattern without structural changes.
5. **No county needed.** Unlike Bark (international), heyBertie is UK-only — county adds URL depth without SEO benefit for our use case.

We keep `/search` as a separate route for free-form search (user-typed queries, postcode lookups). The `/search` route is not intended to rank organically — it serves the functional search use case.

### References

- [TrustATrader — Plumbers in Camberley](https://www.trustatrader.com/plumbers-in-camberley)
- [Checkatrade — Plumber in Camberley](https://www.checkatrade.com/Search/Plumber/in/Camberley)
- [Deliveroo — Restaurants in Camberley](https://deliveroo.co.uk/restaurants/camberley/camberley)
- [Bark — Pet Grooming in Camberley](https://www.bark.com/en/gb/pet-grooming/surrey/camberley/)
- [Good Dog Guide — Dog Grooming in Camberley](https://www.thegooddogguide.com/surrey/camberley/dog-grooming-groomers)
- [SEO URL Structure Best Practices](https://www.highervisibility.com/seo/learn/url-best-practices/)

---

## Dependencies

### From Phase 2 (complete)
- `locations` table with `latitude`, `longitude`, `town`, `city`, `postcode` columns
- `locations.latitude_longitude_index` and `locations.city_postcode_index` indexes
- `Location::getDistanceFrom()` — Haversine distance calculation
- `Location::isWithinServiceRadius()` — mobile groomer service area check
- `Location::servesPostcode()` — postcode-based service area lookup
- `businesses` table with `is_active`, `onboarding_completed`, `verification_status`
- `Business::getAverageRating()`, `getReviewCount()`, `getRatingBreakdown()`
- `services` table with `price`, `price_type`, `is_active`

### From Phase 3 (complete)
- Onboarding creates businesses with locations, services, and geocoded coordinates

### From Phase 4 (complete)
- `BusinessController` listing pages — result cards link to `/{handle}/{slug}`
- `SchemaMarkupService` — reusable schema generation
- `PageViewService` — view tracking pattern
- `GeocodingService` — address/postcode to lat/lng conversion (cached)
- Blade listing templates with marketing layout

### Outputs consumed by later phases
- **Phase 7 (Payments):** "Book Now" on result cards leads to booking flow
- **Phase 10 (Reviews):** Review counts and ratings displayed on result cards
- **Phase 12 (SEO):** City pages feed into sitemap generation; schema markup indexed by Google

---

## Core Architecture

### URL Structure

```
Free-form search (query-based, not intended for organic ranking):
  /search?location=Fulham&service=dog-grooming     → User-typed search
  /search?location=SW6+3JJ&service=dog-grooming    → Postcode search

SEO landing pages (slug = search query, designed to rank organically):
  /dog-grooming-in-london                           → City landing page
  /dog-grooming-in-fulham-london                    → Town-level landing page
  /dog-walking-in-manchester                        → Different service + city

(Landing pages use the same controller + view as /search but with
 pre-resolved coordinates and SEO-optimised title/description/H1.)
```

### Request Flow

```
GET /search?location=Fulham&service=dog-grooming
    |
SearchController@index
    |
GeocodingService::geocode("Fulham") -> {lat, lng}
    |
SearchService::search(lat, lng, filters)
    |
    +-- Query locations with Haversine distance
    +-- Filter: active business, completed onboarding
    +-- Filter: service type, rating, price range, distance
    +-- Sort: distance (default), rating, price
    +-- Paginate (12 per page)
    |
Blade view: search.results
    |
Result cards + filter sidebar + pagination + schema markup
```

### SEO Landing Page Flow

```
GET /dog-grooming-in-london
    |
SearchController@landing
    |
Parse slug: "dog-grooming" + "london" from "{service}-in-{location}"
    |
Resolve location to coordinates (geocode_cache table lookup, no external API call)
    |
Same SearchService::search() pipeline
    |
Same Blade view with SEO-specific title/meta/H1
    |
<title>Dog Grooming in London - Find & Book | heyBertie</title>
<h1>Dog Grooming in London</h1>
<meta name="description" content="Compare 24 trusted dog groomers in London. Read verified reviews, check prices, and book online.">
<link rel="canonical" href="https://heybertie.test/dog-grooming-in-london">
JSON-LD: SearchResultsPage + ItemList
```

---

## Features Overview

| # | Feature | Description |
|---|---------|-------------|
| 1 | SearchController & Routing | Handle search queries, geocode locations, pass to service |
| 2 | SearchService | Build and execute the search query with filters and sorting |
| 3 | Search Results Blade Page | Full results layout with cards, filters, pagination |
| 4 | Result Card Partial | Reusable business card (logo, name, rating, services, distance) |
| 5 | Filter Sidebar | Location type, rating, price range, distance radius |
| 6 | SEO Landing Pages | `/{service}-in-{location}` routes with optimised meta tags |
| 7 | Schema Markup | SearchResultsPage and ItemList structured data |
| 8 | Empty & Loading States | No results messaging, search suggestions |

---

## Feature 1: SearchController & Routing

### Routes

```php
// routes/web.php

// Free-form search (user-typed queries, postcodes)
Route::get('/search', [SearchController::class, 'index'])->name('search');

// SEO landing pages: /{service}-in-{location}
// Matches: /dog-grooming-in-london, /dog-grooming-in-fulham-london, /cat-sitting-in-manchester
Route::get('/{slug}', [SearchController::class, 'landing'])
    ->where('slug', '[a-z-]+-in-[a-z-]+')
    ->name('search.landing');
```

The `{slug}` route uses a regex constraint (`{service}-in-{location}`) to avoid conflicts with business handle routes. The `-in-` separator is the key discriminator — business handles cannot contain "in" as a standalone segment because they use simple slugs.

### Controller

**File:** `app/Http/Controllers/SearchController.php`

```php
class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
        private GeocodingService $geocodingService,
        private SchemaMarkupService $schemaMarkupService,
    ) {}

    // GET /search?location=...&service=...&sort=...&rating=...&distance=...
    public function index(SearchRequest $request): View

    // GET /dog-grooming-in-london (SEO landing page)
    public function landing(string $slug): View|Response
}
```

The `landing()` method parses the slug by splitting on `-in-` to extract the service and location parts, resolves the location to coordinates from the `geocode_cache` table, and delegates to the same search pipeline.

### Query Parameters (for `/search`)

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `location` | string | required | City name, town, or UK postcode |
| `service` | string | `dog-grooming` | Service category slug |
| `sort` | string | `distance` | `distance`, `rating`, `price_low`, `price_high` |
| `rating` | int | null | Minimum rating filter (1-5) |
| `distance` | int | `25` | Max distance in km (5, 10, 25, 50) |
| `type` | string | null | `salon`, `mobile`, `home_based` |
| `page` | int | `1` | Pagination |

Filters on landing pages also use query params (`/dog-grooming-in-london?sort=rating&type=salon`) — the base slug stays clean for SEO while filters are appended as needed.

### Tasks for Feature 1

#### Task 1.1: Create SearchRequest Form Request
- [x] Create `app/Http/Requests/SearchRequest.php`
- [x] Validate: `location` required string, `service` optional in:dog-grooming,dog-walking,cat-sitting
- [x] Validate: `sort` optional in:distance,rating,price_low,price_high
- [x] Validate: `rating` optional integer 1-5
- [x] Validate: `distance` optional integer in:5,10,25,50
- [x] Validate: `type` optional in:salon,mobile,home_based
- [x] Custom messages for location.required

#### Task 1.2: Create SearchController
- [x] Create `app/Http/Controllers/SearchController.php`
- [x] `index()`: geocode location, call SearchService, return Blade view
- [x] `landing()`: parse slug (`{service}-in-{location}`), resolve location from `geocode_cache` table, return Blade view with SEO meta
- [x] Handle geocoding failures gracefully (show "couldn't find location" message)
- [x] Handle invalid landing page slugs with 404
- [x] Pass current filter values back to view for form pre-population

#### Task 1.3: Register Search Routes
- [x] Add `/search` route to `routes/web.php`
- [x] Add `/{slug}` landing page route with `-in-` regex constraint
- [x] Place both before handle catch-all route to avoid conflicts
- [x] Named routes: `search`, `search.landing`

---

## Feature 2: SearchService

### Purpose

Encapsulates the search query logic: Haversine distance calculation in SQL, filtering, sorting, and pagination.

### File: `app/Services/SearchService.php`

```php
class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Business>
     */
    public function search(
        float $latitude,
        float $longitude,
        array $filters = [],
        int $perPage = 12,
    ): LengthAwarePaginator

    /**
     * @return array{latitude: float, longitude: float, name: string}|null
     */
    public function resolveLocation(string $slug): ?array
}
```

### Haversine Distance Query

```sql
SELECT locations.*,
       businesses.*,
       (6371 * acos(
           cos(radians(?)) * cos(radians(latitude))
           * cos(radians(longitude) - radians(?))
           + sin(radians(?)) * sin(radians(latitude))
       )) AS distance
FROM locations
JOIN businesses ON businesses.id = locations.business_id
WHERE businesses.is_active = 1
  AND businesses.onboarding_completed = 1
  AND locations.is_active = 1
  AND locations.latitude IS NOT NULL
  AND locations.longitude IS NOT NULL
HAVING distance <= ?
ORDER BY distance ASC
```

**Note:** SQLite does not support `acos`, `cos`, `sin`, `radians` natively. The service must detect the database driver and fall back to a bounding-box pre-filter + PHP-level Haversine for SQLite (used in tests), while using the raw SQL formula for MySQL/PostgreSQL in production.

### Location Lookup: `geocode_cache` Table

Instead of a hardcoded array, location lookups use the `geocode_cache` table (49,256 UK towns/cities imported from CSV). This avoids external API calls for SEO landing pages while supporting every UK settlement out of the box.

```php
// SearchService::resolveLocation()
$cached = GeocodeCache::where('slug', $slug)->first();
// Returns: { name: "Fulham, London", latitude: 51.4749, longitude: -0.2010 }
```

The `geocode_cache` slug convention matches the URL pattern:
- Cities: `london`, `manchester`, `bristol`
- Towns/areas: `fulham-london`, `chelsea-london`, `stockport-manchester`

Every `geocode_cache` entry automatically creates a valid landing page (e.g. `/dog-grooming-in-fulham-london`). No code changes needed to add new locations — just insert a row into `geocode_cache`.

The `/api/search-suggest` endpoint also queries `geocode_cache` to power the location autocomplete on both the homepage and search results page.

### Tasks for Feature 2

#### Task 2.1: Create SearchService
- [x] Create `app/Services/SearchService.php`
- [x] `search()` method: builds query with Haversine distance, joins businesses, applies filters
- [x] Database driver detection: raw SQL Haversine for MySQL/PostgreSQL, bounding-box + PHP for SQLite
- [x] Filter: `distance` — HAVING distance <= N (or PHP filter for SQLite)
- [x] Filter: `rating` — subquery on reviews for avg rating >= N
- [x] Filter: `type` — locations.location_type IN (...)
- [x] Sort: `distance` (default), `rating` (avg desc), `price_low`/`price_high` (min service price)
- [x] Eager load: `business.subscriptionTier`, `business.reviews` (count only), primary location services
- [x] Paginate 12 per page, append query parameters

#### Task 2.2: Add resolveLocation() Method
- [x] Lookup via `geocode_cache` table (49,256 UK towns/cities) instead of hardcoded array
- [x] Slug-based lookup (lowercase, hyphenated): `london`, `fulham-london`, etc.
- [x] Return `{latitude, longitude, name}` or null

#### Task 2.3: Add Rating Subquery
- [x] Subquery to calculate average rating per business from published reviews
- [x] Used for both filtering (min rating) and sorting (by rating desc)
- [x] Businesses with no reviews sort after rated businesses when sorting by rating

---

## Feature 3: Search Results Blade Page

### Purpose

Server-rendered search results page using the marketing layout (same as listings — SEO-critical).

### File: `resources/views/search/results.blade.php`

### Layout

```
┌──────────────────────────────────────────────────────────┐
│  [Logo]              heyBertie           [Login] [Join]  │
├──────────────────────────────────────────────────────────┤
│  Search bar (pre-populated): [Dog Grooming] [Fulham] [Q] │
├──────────────────────────────────────────────────────────┤
│  "12 dog groomers near Fulham"     Sort: [Distance ▾]   │
├────────────────┬─────────────────────────────────────────┤
│  Filters       │  ┌─────────────────────────────────┐   │
│                │  │ [Logo] Muddy Paws Grooming      │   │
│  Location Type │  │ ★ 4.8 (47) · Salon · 0.3 km    │   │
│  ☐ Salon       │  │ Full Groom from £45 · Verified  │   │
│  ☐ Mobile      │  │ Fulham, London                  │   │
│  ☐ Home-based  │  │                        [View →] │   │
│                │  └─────────────────────────────────┘   │
│  Min Rating    │  ┌─────────────────────────────────┐   │
│  ★ 4+          │  │ [Logo] Paws & Claws            │   │
│  ★ 3+          │  │ ★ 4.5 (23) · Mobile · 1.2 km   │   │
│  ★ Any         │  │ Bath & Brush from £30           │   │
│                │  │ Chelsea, London                 │   │
│  Distance      │  │                        [View →] │   │
│  ○ 5 km        │  └─────────────────────────────────┘   │
│  ● 25 km       │                                        │
│  ○ 50 km       │  ┌─────────────────────────────────┐   │
│                │  │ ... more results ...            │   │
│  [Clear All]   │  └─────────────────────────────────┘   │
│                │                                        │
│                │  [1] [2] [3] ... [Next →]              │
├────────────────┴─────────────────────────────────────────┤
│  Footer                                                  │
└──────────────────────────────────────────────────────────┘
```

Mobile: filters collapse into a slide-out drawer triggered by a "Filters" button.

### View Data

```php
return view('search.results', [
    'results'       => $paginator,        // LengthAwarePaginator of result objects
    'location'      => $locationName,     // "Fulham" or "London" (display name)
    'service'       => $serviceSlug,      // "dog-grooming"
    'serviceName'   => $serviceName,      // "Dog Grooming"
    'coordinates'   => $coords,           // {lat, lng} or null
    'filters'       => $activeFilters,    // Current filter values for form state
    'sort'          => $currentSort,      // Current sort value
    'totalResults'  => $paginator->total(),
    'isLandingPage' => $isLandingPage,    // true for /{service}-in-{location} pages
    'schemaMarkup'  => $schemaJson,       // JSON-LD string
    'canonicalUrl'  => $canonicalUrl,     // e.g. /dog-grooming-in-london
    'metaTitle'     => $metaTitle,
    'metaDescription' => $metaDescription,
]);
```

### Tasks for Feature 3

#### Task 3.1: Create Search Results View
- [x] Create `resources/views/search/results.blade.php` extending `layouts.marketing`
- [x] Search bar at top (pre-populated with current query)
- [x] Results count heading: "12 dog groomers near Fulham"
- [x] Sort dropdown (Distance, Rating, Price low-high, Price high-low)
- [x] Two-column layout: filters sidebar (left) + results grid (right)
- [x] Mobile: single column, filters in collapsible drawer (Alpine.js)
- [x] Pagination (Laravel default, styled with Tailwind)
- [x] Meta tags: `<title>`, `<meta description>`, Open Graph, canonical URL
- [x] JSON-LD schema injection

#### Task 3.2: Create Search Bar Partial
- [x] Create `resources/views/search/partials/search-bar.blade.php`
- [x] Reusable search form (same fields as homepage but horizontal/compact)
- [x] Pre-populate from current query parameters
- [x] Submit re-runs search (GET request)
- [x] Location autocomplete partial (`search/partials/location-autocomplete.blade.php`) with Alpine.js, debounced `/api/search-suggest` calls, keyboard navigation, ARIA attributes

---

## Feature 4: Result Card Partial

### Purpose

Reusable Blade partial for a single business result in the search grid.

### File: `resources/views/search/partials/result-card.blade.php`

### Layout

```
┌─────────────────────────────────────────────┐
│  [Logo]  Muddy Paws Grooming     [Verified] │
│          ★ 4.8 (47 reviews)                 │
│          Salon · Fulham, London · 0.3 km    │
│                                             │
│          Full Groom from £45                │
│          Bath & Brush from £30              │
│                                    [View →] │
└─────────────────────────────────────────────┘
```

### Data Per Card

Each result card receives:
- Business: name, handle, logo_url, verification_status
- Location: town, city, distance (km), location_type
- Rating: average, count
- Services: top 2 services with prices (cheapest featured first)
- Tier: subscription tier slug (for "Book" vs "Contact" CTA)

### Tasks for Feature 4

#### Task 4.1: Create Result Card Partial
- [x] Create `resources/views/search/partials/result-card.blade.php`
- [x] Logo with fallback placeholder (first letter of business name)
- [x] Business name as link to listing page (`/{handle}/{location-slug}`)
- [x] Star rating display (filled stars + count)
- [x] Location type badge (Salon / Mobile / Home-based)
- [x] Town, City display
- [x] Distance in km (rounded to 1 decimal: "0.3 km", "2.1 km")
- [x] Verification badge (checkmark if verified)
- [x] Top 2 services with price formatting
- [x] "View" link to listing page
- [x] Responsive: horizontal card on desktop, stacked on mobile

---

## Feature 5: Filter Sidebar

### Purpose

Refine search results by location type, minimum rating, and distance radius. Filters submit as GET params for shareable/bookmarkable URLs.

### File: `resources/views/search/partials/filters.blade.php`

### Filters

| Filter | Type | Options |
|--------|------|---------|
| Location Type | Checkbox group | Salon, Mobile, Home-based |
| Minimum Rating | Radio group | 4+ stars, 3+ stars, Any |
| Distance | Radio group | 5 km, 10 km, 25 km (default), 50 km |

### Behaviour

- Changing any filter submits the form (Alpine.js `@change` triggers form submit)
- "Clear All" link resets to defaults
- Filters are GET parameters — bookmarkable and shareable
- Mobile: filters in a slide-out drawer (`x-show`, `x-transition`)

### Tasks for Feature 5

#### Task 5.1: Create Filter Sidebar Partial
- [x] Create `resources/views/search/partials/filters.blade.php`
- [x] Location type checkboxes (salon, mobile, home_based)
- [x] Min rating radio buttons (4, 3, null)
- [x] Distance radio buttons (5, 10, 25, 50)
- [x] "Clear All" link
- [x] Alpine.js: auto-submit on change
- [x] Pre-select current filter values from query params

#### Task 5.2: Create Mobile Filter Drawer
- [x] "Filters" button visible on mobile only
- [x] Slide-out drawer with filter controls
- [x] "Apply" button closes drawer and submits
- [x] Alpine.js for open/close state

---

## Feature 6: SEO Landing Pages

### Purpose

Pre-defined landing pages where the URL slug matches the exact Google search query. These are the primary organic traffic acquisition pages. Following the TrustATrader pattern (see [URL Structure Decision](#url-structure-decision-competitive-research) above), the slug IS the keyword.

### URL Pattern

```
City-level pages:
  /dog-grooming-in-london             → "dog grooming in london"
  /dog-grooming-in-manchester         → "dog grooming in manchester"
  /dog-walking-in-birmingham          → "dog walking in birmingham"

Town-level pages (higher intent, lower competition):
  /dog-grooming-in-fulham-london      → "dog grooming in fulham london"
  /dog-grooming-in-chelsea-london     → "dog grooming in chelsea london"
  /cat-sitting-in-camden-london       → "cat sitting in camden london"
```

### SEO Elements

```html
<!-- City page -->
<title>Dog Grooming in London - Find & Book | heyBertie</title>
<meta name="description" content="Compare 24 trusted dog groomers in London. Read verified reviews, check prices, and book online.">
<link rel="canonical" href="https://heybertie.test/dog-grooming-in-london">
<h1>Dog Grooming in London</h1>

<!-- Town page -->
<title>Dog Grooming in Fulham, London - Find & Book | heyBertie</title>
<meta name="description" content="Compare 8 trusted dog groomers in Fulham, London. Read verified reviews, check prices, and book online.">
<link rel="canonical" href="https://heybertie.test/dog-grooming-in-fulham-london">
<h1>Dog Grooming in Fulham, London</h1>
```

### How Landing Pages Differ from Free-Form Search

| Aspect | Free-form `/search` | Landing page `/{service}-in-{location}` |
|--------|---------------------|----------------------------------------|
| Title | "Dog Grooming near Fulham \| heyBertie" | "Dog Grooming in London - Find & Book \| heyBertie" |
| H1 | "12 dog groomers near Fulham" | "Dog Grooming in London" |
| Description | Generic | SEO-optimised with result count |
| Canonical | Self-referencing | Self-referencing |
| Coordinates | From geocoding API | From `geocode_cache` table (no external API call) |
| Indexed | `noindex` (dynamic query results) | Indexed (static landing page) |
| Internal links | None | Cross-linked from other landing pages, footer, homepage |

### Service Type Slugs

```php
private const SERVICES = [
    'dog-grooming' => 'Dog Grooming',
    'dog-walking'  => 'Dog Walking',
    'cat-sitting'  => 'Cat Sitting',
];
```

### Slug Parsing Logic

The `landing()` method splits the slug on the **last** occurrence of `-in-` to extract service and location:

```
"dog-grooming-in-london"        → service: "dog-grooming",  location: "london"
"dog-grooming-in-fulham-london"  → service: "dog-grooming",  location: "fulham-london"
"cat-sitting-in-camden-london"   → service: "cat-sitting",   location: "camden-london"
```

The service is validated against the `SERVICES` constant and the location is looked up in the `geocode_cache` table. Invalid combinations return 404.

### Tasks for Feature 6

#### Task 6.1: Add Landing Page Route
- [x] Route: `GET /{slug}` with regex constraint `[a-z-]+-in-[a-z-]+`
- [x] Parse slug into service and location parts (split on last `-in-`)
- [x] Validate service slug against `SERVICES` constant
- [x] Validate location slug against `geocode_cache` table
- [x] 404 for invalid service or location combinations

#### Task 6.2: SEO Meta Generation
- [x] Dynamic `<title>`: "{Service} in {Location} - Find & Book | heyBertie"
- [x] Dynamic `<meta description>` with result count
- [x] Self-referencing canonical URL
- [ ] Open Graph tags
- [ ] `<meta name="robots" content="index, follow">` (landing pages ARE indexed)
- [x] Pass meta data to Blade view

#### Task 6.3: Internal Cross-Linking
- [ ] "Related areas" section at bottom of landing pages linking to nearby locations
- [ ] "Other services" section linking to other service types in same location
- [x] Update homepage popular city links to use new URL pattern (`/dog-grooming-in-{city}`)
- [ ] Builds a crawlable internal link network (following TrustATrader's pattern)

---

## Feature 7: Schema Markup

### Purpose

Structured data for Google rich results on search pages.

### Schema Types

**SearchResultsPage** (on the page itself):

```json
{
    "@context": "https://schema.org",
    "@type": "SearchResultsPage",
    "name": "Dog Grooming in London",
    "url": "https://heybertie.test/dog-grooming-in-london"
}
```

**ItemList** (the results):

```json
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "numberOfItems": 12,
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "item": {
                "@type": "LocalBusiness",
                "name": "Muddy Paws Grooming",
                "url": "https://heybertie.test/muddy-paws/fulham-london",
                "address": { ... },
                "aggregateRating": { ... }
            }
        }
    ]
}
```

### Tasks for Feature 7

#### Task 7.1: Extend SchemaMarkupService
- [x] Add `generateForSearchResults()` method to existing `SchemaMarkupService`
- [x] Generate `SearchResultsPage` schema
- [x] Generate `ItemList` with `ListItem` entries for each result
- [x] Each `ListItem.item` is a simplified `LocalBusiness` with name, URL, address, rating
- [x] Limit to first page of results (12 items max in schema)

---

## Feature 8: Empty & Error States

### No Results

```
┌──────────────────────────────────────────────┐
│                                              │
│  🔍  No dog groomers found near Fulham      │
│                                              │
│  Try:                                        │
│  • Increasing your search distance           │
│  • Searching a nearby city                   │
│  • Removing filters                          │
│                                              │
│  Popular areas:                                │
│  London · Manchester · Birmingham              │
│                                              │
└──────────────────────────────────────────────┘
```

### Geocoding Failure

```
┌──────────────────────────────────────────────┐
│                                              │
│  📍  We couldn't find that location          │
│                                              │
│  Please try:                                 │
│  • A UK postcode (e.g. SW1A 1AA)            │
│  • A city or town name (e.g. London)        │
│                                              │
└──────────────────────────────────────────────┘
```

### Tasks for Feature 8

#### Task 8.1: Create Empty State Partial
- [x] Create `resources/views/search/partials/no-results.blade.php`
- [x] Suggestions for broadening search
- [x] Links to popular city pages
- [x] Shown when paginator has 0 results

#### Task 8.2: Create Geocoding Error Partial
- [x] Create `resources/views/search/partials/location-error.blade.php`
- [x] Helpful messaging about accepted location formats
- [x] Shown when geocoding returns null

---

## Testing

### Feature Tests

#### Search Route Tests
- [x] `tests/Feature/Search/SearchRouteTest.php`
  - [x] Test: `GET /search?location=London` returns 200
  - [x] Test: `GET /search` without location returns validation error / redirects back
  - [x] Test: `GET /search?location=London&sort=rating` returns sorted results
  - [x] Test: `GET /search?location=London&type=salon` filters by type
  - [x] Test: `GET /search?location=London&rating=4` filters by min rating
  - [x] Test: `GET /dog-grooming-in-london` returns 200
  - [x] Test: `GET /dog-grooming-in-fulham-london` returns 200 (town-level)
  - [x] Test: `GET /dog-grooming-in-invalid-city` returns 404
  - [x] Test: `GET /invalid-service-in-london` returns 404
  - [x] Test: Inactive businesses excluded from results
  - [x] Test: Businesses without completed onboarding excluded

#### Search Result Tests
- [x] `tests/Feature/Search/SearchResultTest.php`
  - [x] Test: Results sorted by distance (default)
  - [x] Test: Results sorted by rating when requested
  - [x] Test: Distance filter excludes far-away results
  - [x] Test: Rating filter excludes low-rated businesses
  - [x] Test: Type filter returns only matching location types
  - [x] Test: Results paginated (12 per page)
  - [x] Test: Result cards contain expected data (name, rating, distance, services)

#### Schema Markup Tests
- [x] `tests/Feature/Search/SearchSchemaTest.php`
  - [x] Test: Search results page includes SearchResultsPage schema
  - [x] Test: ItemList schema contains correct number of items
  - [x] Test: Landing page has correct meta title and description
  - [x] Test: Town-level landing page includes town in title

### Unit Tests

- [x] `tests/Unit/Services/SearchServiceTest.php`
  - [x] Test: resolveLocation returns coordinates for known city
  - [x] Test: resolveLocation returns coordinates for known town (e.g. `fulham-london`)
  - [x] Test: resolveLocation returns null for unknown location
  - [x] Test: search returns results within distance radius
  - [x] Test: search excludes results beyond distance radius
  - [x] Test: search filters by location type
  - [x] Test: search filters by minimum rating
  - [x] Test: search sorts by distance by default
  - [x] Test: search sorts by rating when requested

---

## Final Checklist

### Routes
- [x] `GET /search` → `SearchController@index` (named: `search`)
- [x] `GET /{slug}` → `SearchController@landing` (named: `search.landing`, regex: `[a-z-]+-in-[a-z-]+`)

### Controllers
- [x] `SearchController`

### Form Requests
- [x] `SearchRequest`

### Services
- [x] `SearchService`
- [x] `SchemaMarkupService` (updated — `generateForSearchResults()`)

### Blade Views
- [x] `search/results.blade.php` (main results page)
- [x] `search/partials/search-bar.blade.php`
- [x] `search/partials/result-card.blade.php`
- [x] `search/partials/filters.blade.php`
- [x] `search/partials/no-results.blade.php`
- [x] `search/partials/location-error.blade.php`
- [x] `search/partials/location-autocomplete.blade.php` (Alpine.js autocomplete, shared with homepage)

### Tests
- [x] `SearchRouteTest` (feature)
- [x] `SearchResultTest` (feature)
- [x] `SearchSchemaTest` (feature)
- [x] `SearchServiceTest` (unit)
- [x] `SearchSuggestTest` (feature — `/api/search-suggest` endpoint)

### Dependencies
- [x] No new packages required
- [x] Uses existing `GeocodingService`, `SchemaMarkupService`
- [x] Existing `locations.latitude_longitude_index` supports the Haversine query
