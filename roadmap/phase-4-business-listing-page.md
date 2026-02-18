# Phase 4: Business Listing Page - Detailed Specification

## High-Level Description

This phase builds the public-facing business listing page — the storefront for each groomer on heyBertie. When a customer visits `/@muddy-paws`, they see the business profile, services, reviews, availability, and booking CTA. This is the most important page for SEO and conversion.

## Dependencies

### From Phase 2 (must be complete)
- `businesses` table + `Business` model (profile data, verification status)
- `locations` table + `Location` model (address, opening hours, type)
- `services` table + `Service` model (catalog, pricing)
- `reviews` table + `Review` model (ratings, review text, responses)
- `availability_blocks` table + `AvailabilityBlock` model (opening hours)
- `customers` table (review count, for social proof)
- `HandleService` (handle resolution, redirects)
- `AvailabilityService` (next available slot calculation)

### From Phase 3 (must be complete)
- Onboarding flow creates businesses with handle, location, services
- `verification_documents` table (verification badge display)
- `handle_changes` table (old handle → 301 redirect)
- HandleRedirect middleware (redirect old handles)

### Outputs consumed by later phases
- **Phase 5 (Dashboard):** Business owner can preview their listing from dashboard
- **Phase 6 (Search Results):** Search results link to listing pages; listing cards reuse listing components
- **Phase 7 (Payments):** "Book Now" CTA on listing leads to booking flow with payment (Phase 7)
- **Phase 10 (Reviews):** Review submission form linked from listing page; reviews displayed here
- **Phase 12 (SEO):** Schema markup and meta tags from this phase feed into sitemap/SEO work

---

## Core Architecture

### URL Structure

```
Primary URL (handle-based):
  /@{handle}                    → Single-location business listing
  /@{handle}/{location-slug}    → Multi-location: specific location

Canonical URL (permanent, SEO-stable):
  /p/{id}-{slug}                → Never changes even if handle changes

Old Handle Redirect:
  /@{old-handle}                → 301 → /@{new-handle}
```

### Page Data Flow

```
Request: GET /@muddy-paws
    ↓
HandleRedirect Middleware (check handle_changes for 301)
    ↓
BusinessController@show
    ↓
Load Business + eager load:
  - locations (active, primary first)
  - services (active, ordered by display_order)
  - reviews (published, latest 10, with reviewer)
  - availability blocks (for "Next Available" display)
    ↓
Inertia::render('listing/show', [...])
    ↓
React renders full listing page
```

---

## Features Overview

| # | Feature | Description |
|---|---------|-------------|
| 1 | BusinessController & Routing | Handle resolution, eager loading, canonical URLs, analytics |
| 2 | Listing Page Header | Business name, handle, verification badge, cover image, logo |
| 3 | Gallery Section | Business photos carousel/grid |
| 4 | About Section | Description, business type, location info |
| 5 | Services Section | Service catalog with pricing and duration |
| 6 | Reviews Section | Star rating summary, individual reviews, business responses |
| 7 | Availability Section | Opening hours, next available slot, booking CTA |
| 8 | Location & Map Section | Address, map embed, service area visualization |
| 9 | Contact & CTA Section | Sticky booking CTA, contact options |
| 10 | Schema Markup & SEO | Structured data, meta tags, Open Graph |
| 11 | Multi-Location Support | Location switcher for businesses with multiple locations |

---

## Feature 1: BusinessController & Routing

### Purpose

Resolve handle-based URLs to business profiles, load all required data with eager loading, and track page views.

### Routes

```php
// routes/web.php
Route::get('/@{handle}', [BusinessController::class, 'show'])->name('business.show');
Route::get('/@{handle}/{locationSlug}', [BusinessController::class, 'showLocation'])->name('business.location');
Route::get('/p/{id}-{slug?}', [BusinessController::class, 'showCanonical'])->name('business.canonical');
```

### Controller

**File:** `app/Http/Controllers/BusinessController.php`

```php
class BusinessController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService,
    ) {}

    // GET /@{handle}
    public function show(string $handle): Response|RedirectResponse

    // GET /@{handle}/{locationSlug}
    public function showLocation(string $handle, string $locationSlug): Response|RedirectResponse

    // GET /p/{id}-{slug?}
    public function showCanonical(int $id, ?string $slug = null): RedirectResponse
}
```

### Eager Loading Strategy

```php
$business = Business::query()
    ->where('handle', $handle)
    ->where('is_active', true)
    ->with([
        'locations' => fn ($q) => $q->where('is_active', true)->orderByDesc('is_primary'),
        'services' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
        'reviews' => fn ($q) => $q
            ->where('is_published', true)
            ->with('user:id,name')
            ->latest()
            ->limit(10),
    ])
    ->firstOrFail();
```

### Database Table

#### 1.1 Business_Page_Views Table

**File:** `database/migrations/YYYY_MM_DD_create_business_page_views_table.php`

```php
Schema::create('business_page_views', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');

    // Visitor Info
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent')->nullable();
    $table->string('referrer')->nullable();

    // Source
    $table->enum('source', ['direct', 'search', 'social', 'referral', 'internal'])->default('direct');

    $table->timestamp('viewed_at');

    $table->index(['business_id', 'viewed_at']);
    $table->index(['location_id', 'viewed_at']);
});
```

### Tasks for Feature 1

#### Task 1.1: Create BusinessController
- [ ] Create `app/Http/Controllers/BusinessController.php`
- [ ] Implement `show(string $handle)`:
  - Query business by handle with eager loading
  - Calculate review aggregates (average rating, count, breakdown)
  - Calculate next available slot via AvailabilityService
  - Track page view
  - Return Inertia response
- [ ] Implement `showLocation(string $handle, string $locationSlug)`:
  - Load business + specific location
  - Filter services to those available at location
  - 404 if location slug not found
- [ ] Implement `showCanonical(int $id, ?string $slug = null)`:
  - Load business by ID
  - 301 redirect to `/@{handle}` (current handle URL)
  - Slug is ignored but kept for SEO readability

#### Task 1.2: Register Listing Routes
- [ ] Add handle route: `/@{handle}` → `business.show`
- [ ] Add location route: `/@{handle}/{locationSlug}` → `business.location`
- [ ] Add canonical route: `/p/{id}-{slug?}` → `business.canonical`
- [ ] Ensure handle route regex: `[a-z0-9][a-z0-9-]*` (prevent conflicts with other routes)

#### Task 1.3: Update HandleRedirect Middleware
- [ ] Register middleware from Phase 3 in `bootstrap/app.php`
- [ ] Apply to handle routes only
- [ ] Check `handle_changes` table for old handles
- [ ] 301 redirect from old handle to current handle
- [ ] Cache lookups (old handles rarely change)

#### Task 1.4: Create Business_Page_Views Migration
- [ ] Create migration: `create_business_page_views_table.php`
- [ ] Define view tracking structure
- [ ] Add indexes on `business_id`, `viewed_at`

#### Task 1.5: Create BusinessPageView Model
- [ ] Create `app/Models/BusinessPageView.php`
- [ ] Define fillable fields
- [ ] Add casts: `viewed_at` => `datetime`
- [ ] Relationships: `belongsTo(Business::class)`, `belongsTo(Location::class)->nullable()`
- [ ] Add scopes: `forPeriod(Carbon $start, Carbon $end)`, `forBusiness(Business $business)`

#### Task 1.6: Create PageView Tracking Service
- [ ] Create `app/Services/PageViewService.php`
- [ ] Method: `trackView(Business $business, ?Location $location, Request $request)`
  - Extract IP, user agent, referrer
  - Determine source (search, social, direct, referral)
  - Deduplicate: same IP + business within 30 minutes = 1 view
  - Queue the insert (don't block page load)
- [ ] Method: `getViewsForPeriod(Business $business, Carbon $start, Carbon $end)`

---

## Feature 2: Listing Page Header

### Purpose

First thing visitors see. Establishes business identity and trust.

### Layout

```
┌─────────────────────────────────────────────┐
│          [Cover Image / Gradient]            │
│                                              │
│   [Logo]  Business Name                      │
│           @handle · Verified ✓ · ★ 4.8 (47) │
│           Salon · Fulham, London             │
│                                              │
│   [Book Now]  [Call]  [Share]                │
└─────────────────────────────────────────────┘
```

### Props

```typescript
interface ListingHeaderProps {
    business: {
        name: string;
        handle: string;
        description: string | null;
        logo_url: string | null;
        cover_image_url: string | null;
        verification_status: 'pending' | 'verified' | 'rejected';
        subscription_tier: string;
    };
    location: {
        name: string;
        city: string;
        location_type: string;
    };
    rating: {
        average: number;
        count: number;
    };
}
```

### Tasks for Feature 2

#### Task 2.1: Create Listing Header Component
- [ ] Create `resources/js/components/listing/listing-header.tsx`
- [ ] Cover image with gradient overlay (or branded gradient if no cover image)
- [ ] Logo display (circular, overlapping cover image)
- [ ] Business name (h1)
- [ ] Handle display with @ prefix
- [ ] Verification badge (green checkmark if verified, "Pending" if pending)
- [ ] Star rating with review count
- [ ] Location type badge (Salon / Mobile / Home-based)
- [ ] City/area display
- [ ] Action buttons: Book Now (primary), Call, Share
- [ ] Responsive: stack vertically on mobile

---

## Feature 3: Gallery Section

### Purpose

Showcase the grooming salon, finished dogs, and work quality through photos.

### Notes

- In Phase 4, gallery uses the cover image and logo only (limited photos)
- Full gallery management (upload, reorder, delete) is a dashboard feature (Phase 5)
- Future enhancement: before/after photo pairs

### Tasks for Feature 3

#### Task 3.1: Create Gallery Component
- [ ] Create `resources/js/components/listing/listing-gallery.tsx`
- [ ] Photo grid (3-column on desktop, 2 on tablet, 1 on mobile)
- [ ] Lightbox on click (full-screen view with navigation)
- [ ] Fallback: show business type illustration if no photos
- [ ] Lazy loading for images below the fold

---

## Feature 4: About Section

### Purpose

Business description, specialties, and key information.

### Layout

```
┌──────────────────────────────────────────┐
│  About Muddy Paws Grooming               │
│                                          │
│  "We're a family-run grooming salon in   │
│   Fulham specializing in nervous dogs..." │
│                                          │
│  🐕 Dog specialist                       │
│  📍 Salon-based in Fulham                │
│  ⏰ Open Mon-Sat                         │
│  ✨ 5 years experience                   │
└──────────────────────────────────────────┘
```

### Tasks for Feature 4

#### Task 4.1: Create About Component
- [ ] Create `resources/js/components/listing/listing-about.tsx`
- [ ] Business description (with "Read more" truncation if long)
- [ ] Key facts list with icons:
  - Business type (Salon / Mobile / Home-based)
  - Location area
  - Opening days summary
  - Years in business (future: calculated from profile)
- [ ] Responsive layout

---

## Feature 5: Services Section

### Purpose

Display all services with pricing, duration, and booking CTAs.

### Layout

```
┌──────────────────────────────────────────┐
│  Services                                │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │ Full Groom                         │  │
│  │ Complete wash, dry, cut & style    │  │
│  │ 90 min · £45.00         [Book]     │  │
│  └────────────────────────────────────┘  │
│  ┌────────────────────────────────────┐  │
│  │ Bath & Brush                       │  │
│  │ Wash and brush out                 │  │
│  │ 60 min · From £30.00    [Book]     │  │
│  └────────────────────────────────────┘  │
│  ┌────────────────────────────────────┐  │
│  │ Nail Trim                          │  │
│  │ Quick nail clip and file           │  │
│  │ 15 min · £10.00         [Book]     │  │
│  └────────────────────────────────────┘  │
└──────────────────────────────────────────┘
```

### Props

```typescript
interface ListingServicesProps {
    services: Array<{
        id: number;
        name: string;
        description: string | null;
        duration_minutes: number;
        price: number | null;
        price_type: 'fixed' | 'from' | 'call';
        is_featured: boolean;
    }>;
    canBook: boolean; // false if free tier (no booking system)
}
```

### Tasks for Feature 5

#### Task 5.1: Create Services Component
- [ ] Create `resources/js/components/listing/listing-services.tsx`
- [ ] Service cards in list layout
- [ ] Each card shows: name, description, duration, formatted price
- [ ] Price formatting: "£45.00" (fixed), "From £30.00" (from), "Price on request" (call)
- [ ] Duration formatting: "90 min" or "1 hr 30 min"
- [ ] "Book" button per service (disabled if free tier, shows "Contact to book")
- [ ] Featured services highlighted with subtle badge
- [ ] Empty state: "No services listed yet" (shouldn't happen post-onboarding)

#### Task 5.2: Create ServiceCard Component
- [ ] Create `resources/js/components/listing/service-card.tsx`
- [ ] Reusable card for service display
- [ ] Used on listing page and search results preview

---

## Feature 6: Reviews Section

### Purpose

Social proof through verified customer reviews. Shows aggregate rating and individual reviews.

### Layout

```
┌──────────────────────────────────────────┐
│  Reviews                    ★ 4.8 (47)   │
│                                          │
│  ★★★★★  28  ████████████████████░░  60%  │
│  ★★★★☆  12  █████████░░░░░░░░░░░  26%  │
│  ★★★☆☆   4  ███░░░░░░░░░░░░░░░░░   9%  │
│  ★★☆☆☆   2  █░░░░░░░░░░░░░░░░░░░   4%  │
│  ★☆☆☆☆   1  ░░░░░░░░░░░░░░░░░░░░   2%  │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │ ★★★★★  Sarah M. · Verified ✓     │  │
│  │ 2 weeks ago                        │  │
│  │ "Buddy looked amazing! So patient  │  │
│  │  with him. Will definitely return." │  │
│  │                                    │  │
│  │ 💬 Response from Muddy Paws:      │  │
│  │ "Thank you Sarah! Buddy was a     │  │
│  │  pleasure. See you next time!"     │  │
│  └────────────────────────────────────┘  │
│                                          │
│  [Load More Reviews]                     │
└──────────────────────────────────────────┘
```

### Props

```typescript
interface ListingReviewsProps {
    rating: {
        average: number;
        count: number;
        breakdown: Record<number, number>; // { 5: 28, 4: 12, 3: 4, 2: 2, 1: 1 }
    };
    reviews: Array<{
        id: number;
        rating: number;
        review_text: string | null;
        is_verified: boolean;
        created_at: string;
        user: {
            name: string; // First name + last initial
        };
        response_text: string | null;
        responded_at: string | null;
    }>;
    hasMore: boolean;
}
```

### Tasks for Feature 6

#### Task 6.1: Create Reviews Component
- [ ] Create `resources/js/components/listing/listing-reviews.tsx`
- [ ] Rating summary header (average, total count)
- [ ] Star distribution chart (horizontal bar chart)
- [ ] Individual review cards
- [ ] "Load More" button (loads next 10 reviews via Inertia partial reload)

#### Task 6.2: Create ReviewCard Component
- [ ] Create `resources/js/components/listing/review-card.tsx`
- [ ] Star display (filled/empty stars)
- [ ] Reviewer name (first name + last initial for privacy)
- [ ] "Verified" badge if review is from a real booking
- [ ] Relative time display ("2 weeks ago")
- [ ] Review text
- [ ] Business response (indented, different styling)
- [ ] Responsive layout

#### Task 6.3: Create RatingBreakdown Component
- [ ] Create `resources/js/components/listing/rating-breakdown.tsx`
- [ ] Horizontal bar chart for 5-star to 1-star distribution
- [ ] Percentage and count display
- [ ] Animated fill on page load

#### Task 6.4: Implement Review Pagination
- [ ] Add API endpoint: `GET /@{handle}/reviews?page=2`
- [ ] Return paginated reviews (10 per page)
- [ ] Use Inertia partial reloads for smooth loading
- [ ] Add to BusinessController: `loadMoreReviews(string $handle, Request $request)`

#### Task 6.5: Add Review Aggregation to Business Model
- [ ] Method: `getAverageRating(): float` (cached, recalculated when review added)
- [ ] Method: `getReviewCount(): int`
- [ ] Method: `getRatingBreakdown(): array`
- [ ] Cache key: `business:{id}:rating` with tag-based invalidation

---

## Feature 7: Availability Section

### Purpose

Show when the business is open and the next available booking slot.

### Layout

```
┌──────────────────────────────────────────┐
│  Opening Hours                           │
│                                          │
│  Monday      9:00 AM – 5:00 PM          │
│  Tuesday     9:00 AM – 5:00 PM          │
│  Wednesday   9:00 AM – 5:00 PM          │
│  Thursday    9:00 AM – 5:00 PM          │
│  Friday      9:00 AM – 5:00 PM          │
│  Saturday    9:00 AM – 1:00 PM          │
│  Sunday      Closed                      │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │ Next available: Tomorrow at 10:00  │  │
│  │ [Book This Slot]                   │  │
│  └────────────────────────────────────┘  │
└──────────────────────────────────────────┘
```

### Props

```typescript
interface ListingAvailabilityProps {
    openingHours: Record<string, { open: string; close: string } | null>;
    // { monday: { open: "09:00", close: "17:00" }, sunday: null, ... }
    nextAvailable: {
        datetime: string;
        formatted: string; // "Tomorrow at 10:00 AM"
    } | null;
    canBook: boolean;
}
```

### Tasks for Feature 7

#### Task 7.1: Create Availability Component
- [ ] Create `resources/js/components/listing/listing-availability.tsx`
- [ ] Opening hours table (day → hours, or "Closed")
- [ ] Highlight today's row
- [ ] "Currently Open" / "Currently Closed" badge based on current time
- [ ] Next available slot callout with booking button
- [ ] If free tier: show hours but no booking functionality

#### Task 7.2: Next Available Slot Calculation
- [ ] Use `AvailabilityService::getNextAvailableSlot()` from Phase 2
- [ ] Method: `getNextAvailableSlot(Location $location, ?int $serviceDuration = null): ?Carbon`
  - Check availability blocks for next 14 days
  - Exclude booked slots
  - Return first open slot
- [ ] Cache result for 5 minutes (recalculated frequently)
- [ ] Format as human-readable: "Today at 2:00 PM", "Tomorrow at 10:00 AM", "Monday at 9:00 AM"

---

## Feature 8: Location & Map Section

### Purpose

Show where the business is located with a map and address details. For mobile groomers, show service area.

### Layout

```
┌──────────────────────────────────────────┐
│  Location                                │
│                                          │
│  ┌──────────────────┐ 123 High Street    │
│  │                  │ Fulham             │
│  │   [Map Embed]    │ London SW6 3JJ     │
│  │                  │                    │
│  │                  │ [Get Directions]   │
│  └──────────────────┘                    │
│                                          │
│  For mobile groomers:                    │
│  "We serve: Fulham, Chelsea, Kensington" │
│  "Service radius: 10 km"                │
└──────────────────────────────────────────┘
```

### Tasks for Feature 8

#### Task 8.1: Create Location Component
- [ ] Create `resources/js/components/listing/listing-location.tsx`
- [ ] Address display (formatted)
- [ ] Static map image (Google Maps Static API or OpenStreetMap embed)
- [ ] "Get Directions" link (opens Google Maps with destination)
- [ ] For mobile groomers: list service areas served
- [ ] Service radius visualization (text description, not map overlay for now)

#### Task 8.2: Map Integration
- [ ] Use static map image to avoid JavaScript SDK overhead
- [ ] Google Maps Static API: `https://maps.googleapis.com/maps/api/staticmap?...`
- [ ] Fallback: OpenStreetMap iframe embed (no API key needed)
- [ ] Add map API key to `.env` configuration
- [ ] Lazy load map image (below the fold)

---

## Feature 9: Contact & CTA Section

### Purpose

Sticky booking CTA and contact options that persist as the user scrolls.

### Sticky CTA Bar (Mobile)

```
┌──────────────────────────────────────────┐
│  Full Groom from £45  ·  ★ 4.8  [Book]  │
└──────────────────────────────────────────┘
```

### Contact Options

```
┌──────────────────────────────────────────┐
│  Contact Muddy Paws                      │
│                                          │
│  📞 020 7123 4567    [Call]              │
│  ✉️  hello@...        [Email]            │
│  🌐 muddypaws.co.uk  [Website]          │
└──────────────────────────────────────────┘
```

### Tasks for Feature 9

#### Task 9.1: Create CTA Component
- [ ] Create `resources/js/components/listing/listing-cta.tsx`
- [ ] Prominent "Book Now" button (if Solo/Salon tier)
- [ ] "Contact to Book" button (if Free tier)
- [ ] Starting price display
- [ ] Star rating badge

#### Task 9.2: Create Sticky Mobile CTA
- [ ] Create `resources/js/components/listing/sticky-booking-bar.tsx`
- [ ] Fixed bottom bar on mobile (hidden on desktop)
- [ ] Shows on scroll past the header CTA
- [ ] Business name, price summary, and Book button
- [ ] Smooth slide-in animation

#### Task 9.3: Create Contact Section
- [ ] Create `resources/js/components/listing/listing-contact.tsx`
- [ ] Phone number with click-to-call link (`tel:`)
- [ ] Email with mailto link
- [ ] Website link (opens in new tab)
- [ ] Only show contact methods that exist (some businesses may only have phone)

#### Task 9.4: Share Functionality
- [ ] Create `resources/js/components/listing/share-button.tsx`
- [ ] Native Web Share API on mobile (fallback to copy link)
- [ ] Share options: Copy link, WhatsApp, Facebook, Twitter/X
- [ ] Use `use-clipboard` hook for copy functionality

---

## Feature 10: Schema Markup & SEO

### Purpose

Structured data for Google rich results, meta tags for social sharing, and SEO optimization.

### Schema.org Markup Types

1. **LocalBusiness** - Business identity and location
2. **Service** - Individual grooming services
3. **AggregateRating** - Star rating summary
4. **Review** - Individual customer reviews
5. **OpeningHoursSpecification** - Business hours

### JSON-LD Output Example

```json
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Muddy Paws Grooming",
    "url": "https://bertie.co.uk/@muddy-paws",
    "image": "https://bertie.co.uk/storage/logos/muddy-paws.jpg",
    "telephone": "+442071234567",
    "email": "hello@muddypaws.co.uk",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "123 High Street",
        "addressLocality": "Fulham",
        "addressRegion": "London",
        "postalCode": "SW6 3JJ",
        "addressCountry": "GB"
    },
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": 51.4749,
        "longitude": -0.2010
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "47"
    },
    "openingHoursSpecification": [...],
    "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Grooming Services",
        "itemListElement": [...]
    }
}
```

### Meta Tags

```html
<title>Muddy Paws Grooming - Dog Grooming in Fulham | heyBertie</title>
<meta name="description" content="Professional dog grooming in Fulham, London. Full groom from £45. ★ 4.8 (47 reviews). Book online today.">

<!-- Open Graph -->
<meta property="og:title" content="Muddy Paws Grooming - Dog Grooming in Fulham">
<meta property="og:description" content="Professional dog grooming...">
<meta property="og:image" content="https://bertie.co.uk/storage/logos/muddy-paws.jpg">
<meta property="og:url" content="https://bertie.co.uk/@muddy-paws">
<meta property="og:type" content="business.business">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
```

### Tasks for Feature 10

#### Task 10.1: Create Schema Markup Service
- [ ] Create `app/Services/SchemaMarkupService.php`
- [ ] Method: `generateLocalBusiness(Business $business, Location $location): array`
- [ ] Method: `generateServices(Collection $services): array`
- [ ] Method: `generateAggregateRating(float $average, int $count): array`
- [ ] Method: `generateReviews(Collection $reviews): array`
- [ ] Method: `generateOpeningHours(array $hours): array`
- [ ] Method: `toJsonLd(array $data): string` - Convert to `<script type="application/ld+json">`

#### Task 10.2: Create SEO Head Component
- [ ] Create `resources/js/components/listing/listing-seo-head.tsx`
- [ ] Use Inertia's `Head` component for meta tags
- [ ] Dynamic title: `{business.name} - {service_type} in {city} | heyBertie`
- [ ] Dynamic description with rating and price info
- [ ] Open Graph tags
- [ ] Twitter Card tags
- [ ] Canonical URL (`/p/{id}-{slug}`)

#### Task 10.3: Inject JSON-LD from Controller
- [ ] Generate schema markup in BusinessController
- [ ] Pass as prop to Inertia page
- [ ] Render in `<Head>` as `<script type="application/ld+json">`

---

## Feature 11: Multi-Location Support

### Purpose

Businesses with multiple locations (Salon tier) need a way to switch between location views.

### UX

- Single location: No switcher, show location directly
- Multiple locations: Location tabs/dropdown at top of listing

### Layout

```
┌──────────────────────────────────────────┐
│  📍 Fulham Salon  |  Chelsea Salon  |    │
│     (active)         Mobile Service      │
└──────────────────────────────────────────┘
```

### Tasks for Feature 11

#### Task 11.1: Create Location Switcher Component
- [ ] Create `resources/js/components/listing/location-switcher.tsx`
- [ ] Tabs for each location (desktop)
- [ ] Dropdown select (mobile)
- [ ] Active location highlighted
- [ ] Navigates to `/@{handle}/{location-slug}`
- [ ] Only rendered if business has > 1 active location

#### Task 11.2: Filter Content by Location
- [ ] Services section: filter to services available at selected location
- [ ] Availability section: show hours for selected location
- [ ] Map section: show selected location on map
- [ ] Reviews: show all business reviews (not location-specific)

---

## Listing Page (Main Inertia Page)

### File

**`resources/js/pages/listing/show.tsx`**

### Props

```typescript
interface ListingShowProps {
    business: {
        id: number;
        name: string;
        handle: string;
        slug: string;
        description: string | null;
        logo_url: string | null;
        cover_image_url: string | null;
        phone: string | null;
        email: string | null;
        website: string | null;
        verification_status: string;
        subscription_tier: string;
    };
    location: {
        id: number;
        name: string;
        slug: string;
        location_type: string;
        address_line_1: string;
        address_line_2: string | null;
        city: string;
        postcode: string;
        county: string | null;
        latitude: number | null;
        longitude: number | null;
        is_mobile: boolean;
        service_radius_km: number | null;
        opening_hours: Record<string, { open: string; close: string } | null>;
    };
    locations: Array<{ id: number; name: string; slug: string }>; // For switcher
    services: Array<{
        id: number;
        name: string;
        description: string | null;
        duration_minutes: number;
        price: number | null;
        price_type: string;
        is_featured: boolean;
    }>;
    rating: {
        average: number;
        count: number;
        breakdown: Record<number, number>;
    };
    reviews: Array<{
        id: number;
        rating: number;
        review_text: string | null;
        is_verified: boolean;
        created_at: string;
        user: { name: string };
        response_text: string | null;
        responded_at: string | null;
    }>;
    hasMoreReviews: boolean;
    nextAvailable: { datetime: string; formatted: string } | null;
    canBook: boolean;
    schemaMarkup: string; // JSON-LD string
}
```

### Tasks for Listing Page

#### Task LP.1: Create Listing Show Page
- [ ] Create `resources/js/pages/listing/show.tsx`
- [ ] Compose all section components:
  1. `<ListingHeader />`
  2. `<LocationSwitcher />` (if multi-location)
  3. `<ListingGallery />`
  4. `<ListingAbout />`
  5. `<ListingServices />`
  6. `<ListingReviews />`
  7. `<ListingAvailability />`
  8. `<ListingLocation />`
  9. `<ListingContact />`
  10. `<StickyBookingBar />` (mobile only)
  11. `<ListingSeoHead />` (in Head)
- [ ] Two-column layout on desktop (main content + sidebar with CTA/hours)
- [ ] Single column on mobile with sticky bottom CTA
- [ ] Smooth scroll navigation between sections

#### Task LP.2: Create Listing Layout
- [ ] Create `resources/js/layouts/listing-layout.tsx`
- [ ] Minimal header (logo + navigation, no sidebar)
- [ ] Footer with links (About, Terms, Privacy, Contact)
- [ ] Breadcrumb: Home > Search > {Business Name}
- [ ] Mobile-optimized

---

## Testing

### Feature Tests

#### Listing Route Tests
- [ ] `tests/Feature/Listing/ListingRouteTest.php`
  - [ ] Test: `/@{handle}` returns 200 for active verified business
  - [ ] Test: `/@{handle}` returns 404 for non-existent handle
  - [ ] Test: `/@{handle}` returns 404 for inactive business
  - [ ] Test: `/@{old-handle}` returns 301 redirect to new handle
  - [ ] Test: `/p/{id}-{slug}` returns 301 redirect to handle URL
  - [ ] Test: `/@{handle}/{location}` returns 200 for valid location
  - [ ] Test: `/@{handle}/{location}` returns 404 for invalid location slug

#### Listing Data Tests
- [ ] `tests/Feature/Listing/ListingDataTest.php`
  - [ ] Test: Business data loaded with correct relationships
  - [ ] Test: Only active services returned
  - [ ] Test: Only published reviews returned
  - [ ] Test: Reviews limited to 10 (pagination)
  - [ ] Test: Rating aggregation calculated correctly
  - [ ] Test: Next available slot calculated
  - [ ] Test: Multi-location filters services by location

#### Page View Tracking Tests
- [ ] `tests/Feature/Listing/PageViewTrackingTest.php`
  - [ ] Test: Page view recorded on listing visit
  - [ ] Test: Duplicate views deduplicated (same IP within 30 min)
  - [ ] Test: Source correctly determined (referrer parsing)

#### Schema Markup Tests
- [ ] `tests/Feature/Listing/SchemaMarkupTest.php`
  - [ ] Test: LocalBusiness schema generated correctly
  - [ ] Test: Service schema includes all active services
  - [ ] Test: AggregateRating only included when reviews exist
  - [ ] Test: OpeningHours generated from location hours

### Unit Tests

- [ ] `tests/Unit/Services/SchemaMarkupServiceTest.php`
  - [ ] Test: generateLocalBusiness returns valid schema
  - [ ] Test: generateServices handles all price types
  - [ ] Test: generateOpeningHours handles closed days

- [ ] `tests/Unit/Services/PageViewServiceTest.php`
  - [ ] Test: Source detection from referrer
  - [ ] Test: Deduplication logic

---

## Final Checklist

### Migrations
- [ ] `create_business_page_views_table`

### Models
- [ ] BusinessPageView (new)

### Controllers
- [ ] BusinessController

### Services
- [ ] SchemaMarkupService
- [ ] PageViewService

### Inertia Pages
- [ ] `pages/listing/show.tsx`

### Layouts
- [ ] `layouts/listing-layout.tsx`

### Components
- [ ] `components/listing/listing-header.tsx`
- [ ] `components/listing/listing-gallery.tsx`
- [ ] `components/listing/listing-about.tsx`
- [ ] `components/listing/listing-services.tsx`
- [ ] `components/listing/service-card.tsx`
- [ ] `components/listing/listing-reviews.tsx`
- [ ] `components/listing/review-card.tsx`
- [ ] `components/listing/rating-breakdown.tsx`
- [ ] `components/listing/listing-availability.tsx`
- [ ] `components/listing/listing-location.tsx`
- [ ] `components/listing/listing-contact.tsx`
- [ ] `components/listing/listing-cta.tsx`
- [ ] `components/listing/sticky-booking-bar.tsx`
- [ ] `components/listing/share-button.tsx`
- [ ] `components/listing/location-switcher.tsx`
- [ ] `components/listing/listing-seo-head.tsx`

### Routes
- [ ] `/@{handle}` → `business.show`
- [ ] `/@{handle}/{locationSlug}` → `business.location`
- [ ] `/p/{id}-{slug?}` → `business.canonical`
- [ ] `/@{handle}/reviews` → review pagination API

### Tests
- [ ] ListingRouteTest
- [ ] ListingDataTest
- [ ] PageViewTrackingTest
- [ ] SchemaMarkupTest
- [ ] SchemaMarkupServiceTest (unit)
- [ ] PageViewServiceTest (unit)