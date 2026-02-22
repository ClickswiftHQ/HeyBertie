# Address & Geocoding Strategy

## Decision Summary

Replace Google Maps API with a two-tier approach: a local postcodes table for search geocoding (free, no API calls) and Ideal Postcodes for address entry in forms (paid, per-lookup).

---

## Background

Phase 6 (Search Results) exposed the need for reliable geocoding. The homepage search form requires converting user input (postcodes, city names) into lat/lng coordinates. Business onboarding requires full address entry. Google Maps Geocoding API was the initial approach but adds cost and an external dependency for every search.

---

## Architecture

### Tier 1: Own Database (Free, No API Calls)

**Postcode geocoding** — Import the ONS Postcode Directory (ONSPD) into a `postcodes` table. Published quarterly by the Office for National Statistics, free to use. Contains every UK postcode (~1.7 million rows) with lat/lng, ward, parish, county, region.

| Column | Purpose |
|--------|---------|
| `postcode` | Primary key (normalised, no spaces) |
| `latitude` | WGS84 latitude |
| `longitude` | WGS84 longitude |
| `town` | Post town |
| `county` | County name |
| `region` | Region/country |

**City/town geocoding** — Already implemented as `SearchService::LOCATIONS` constant with 30 hardcoded entries (20 cities + 10 London towns). Expandable over time based on Google Search Console data.

**SEO landing pages** — Use the hardcoded locations list. No API calls for crawlers.

### Tier 2: Ideal Postcodes API (Paid, Per-Lookup)

**Address entry in forms** — When a user enters a postcode during onboarding or registration, call Ideal Postcodes to get a list of individual street addresses at that postcode. The user picks their address from the dropdown.

- Postcode → list of full addresses (line 1, line 2, town, county, postcode)
- Includes UPRN (Unique Property Reference Number) and rooftop geocodes
- Royal Mail PAF data, updated daily
- Pricing: pay-as-you-go from ~3-4p per lookup

**Website:** https://ideal-postcodes.co.uk/

### Caching Strategy

Every address lookup result from Ideal Postcodes is cached in our own database. The search flow becomes:

```
User searches "SK1 3AA"
  → Check postcodes table for lat/lng (Tier 1, free) → Found? Search.
  → Not found? Check cached lookups → Found? Search.
  → Not found? Call Ideal Postcodes API → Cache result → Search.
```

Over time, the cache fills from real user searches and API calls drop toward zero for repeat postcodes.

---

## What This Replaces

| Current | Replacement |
|---------|-------------|
| `GeocodingService::geocode()` via Google Maps | Postcodes table lookup + Ideal Postcodes fallback |
| `GeocodingService::reverseGeocode()` via Google Maps | Not needed — address entry uses postcode lookup instead |
| `GOOGLE_MAPS_API_KEY` env var | `IDEAL_POSTCODES_API_KEY` env var |

---

## Use Cases

| Use Case | Source | API Call? |
|----------|--------|-----------|
| Search by postcode (e.g. "SK1 3AA") | `postcodes` table (ONSPD) | No |
| Search by city name (e.g. "London") | `SearchService::LOCATIONS` constant | No |
| SEO landing page (e.g. `/dog-grooming-in-london`) | `SearchService::LOCATIONS` constant | No |
| Address entry during onboarding | Ideal Postcodes API (cached) | Yes (first time) |
| Address entry during registration | Ideal Postcodes API (cached) | Yes (first time) |

---

## Implementation Phases

### Phase 1: ONSPD Import
- Create `postcodes` migration (`postcode`, `latitude`, `longitude`, `town`, `county`, `region`)
- Artisan command to import ONSPD CSV (~1.7M rows)
- Update `GeocodingService` to check postcodes table first

### Phase 2: Ideal Postcodes Integration
- Add `ideal-postcodes` config and API key
- Create `AddressLookupService` for postcode → address list
- Build postcode lookup UI component (input postcode → select address dropdown)
- Integrate into onboarding address step

### Phase 3: Cache Layer
- Cache Ideal Postcodes responses in database
- Search checks cache before API
- Track cache hit rate for monitoring

### Phase 4: Remove Google Maps
- Remove `GOOGLE_MAPS_API_KEY` from config/env
- Remove Google Maps HTTP calls from `GeocodingService`
- Full test coverage for new geocoding flow

---

## Data Sources

| Source | Data | Cost | Update Frequency |
|--------|------|------|-----------------|
| [ONS Postcode Directory (ONSPD)](https://geoportal.statistics.gov.uk/) | Postcode → lat/lng, town, county, region | Free (Open Government Licence) | Quarterly |
| [Ideal Postcodes](https://ideal-postcodes.co.uk/) | Postcode → full street addresses (Royal Mail PAF) | ~3-4p per lookup | Daily |
| Hardcoded `LOCATIONS` constant | City/town name → lat/lng | Free | Manual (as needed) |
