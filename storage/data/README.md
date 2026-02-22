# UK Towns Data Import

## Source

CSV from [townslist.co.uk](https://www.townslist.co.uk/) containing ~49,257 UK settlements with name, county, country, postcode sector, settlement type, and coordinates.

## Import Command

```bash
php -d memory_limit=256M artisan towns:import storage/data/uk-towns.csv
```

Requires a fresh migration first (`php artisan migrate:fresh`) as the command uses upsert on the `geocode_cache` table.

## Schema

| Column | Purpose |
|--------|---------|
| `name` | Raw place name from CSV (e.g. "Fulham", "Abbey Dore") — never modified, preserves data integrity |
| `display_name` | Friendly label for UI (e.g. "Fulham, London", "Abbey Dore, Herefordshire") |
| `slug` | URL-safe unique identifier derived from `display_name` (e.g. `fulham-london`) |
| `county` | Raw county from CSV (e.g. "Greater London", "City of Edinburgh") — never modified |
| `country` | Raw country from CSV (e.g. "England", "Scotland") |
| `postcode_sector` | Outward code + first inward digit (e.g. "SW6 1", "GU7 2") |
| `settlement_type` | City, Town, Village, Hamlet, Suburban Area, Locality |

## County Normalisation

The `display_name` and `slug` use normalised county names for readability and SEO. The raw `county` column is never modified.

| CSV County | Display County | Reason |
|------------|---------------|--------|
| Greater London | London | "Fulham, London" reads better than "Fulham, Greater London" |
| Greater Manchester | Manchester | Same pattern as above |
| City of Edinburgh | Edinburgh | Removes administrative prefix |
| City of Glasgow | Glasgow | Removes administrative prefix |
| City of Aberdeen | Aberdeen | Removes administrative prefix |
| City of Dundee | Dundee | Removes administrative prefix |

All other counties are used as-is (e.g. "North Yorkshire", "West Midlands", "Surrey", "Merseyside").

## Slug Generation Rules

Slugs always follow the `{name}-{county}` pattern for SEO consistency, with three tiers of disambiguation:

### Tier 1: Name + County (default)

Every entry includes its normalised county in the slug unless the name matches the county (see Tier 0).

```
Fulham (Greater London) → display_name: "Fulham, London" → slug: fulham-london
Abbey Dore (Herefordshire) → display_name: "Abbey Dore, Herefordshire" → slug: abbey-dore-herefordshire
Salford (Greater Manchester) → display_name: "Salford, Manchester" → slug: salford-manchester
Leith (City of Edinburgh) → display_name: "Leith, Edinburgh" → slug: leith-edinburgh
```

### Tier 0: Name only (when name matches normalised county)

When the place name equals the normalised county, the county suffix is skipped to avoid redundancy.

```
London (Greater London → "London") → display_name: "London" → slug: london
Manchester (Greater Manchester → "Manchester") → display_name: "Manchester" → slug: manchester
Edinburgh (City of Edinburgh → "Edinburgh") → display_name: "Edinburgh" → slug: edinburgh
```

### Tier 2: Name + County + Postcode Sector (collision resolution)

When multiple places share the same name AND normalised county, the postcode sector is appended to the slug (not the display name).

```
Acklam (North Yorkshire, TS5 7) → display_name: "Acklam, North Yorkshire" → slug: acklam-north-yorkshire-ts5-7
Acklam (North Yorkshire, YO17 9) → display_name: "Acklam, North Yorkshire" → slug: acklam-north-yorkshire-yo17-9
```

### Numeric Fallback

If a slug collision still exists after all tiers (e.g. different place names that slugify identically, or entries with empty postcode sectors), a numeric suffix is appended: `-2`, `-3`, etc. This is a safety net — it rarely triggers.

## How Display Names Are Used

- **Landing page titles**: "Dog Grooming in Fulham, London"
- **Search suggestions**: autocomplete dropdown shows `display_name`
- **URL slugs**: `/dog-grooming-in-fulham-london`
- **resolveLocation()**: returns `display_name` as the `name` field

The raw `name` column is preserved for data integrity and potential future matching against external datasets.

## Adding New County Mappings

If new administrative county names need normalising, add them to the `COUNTY_MAP` constant in `ImportTownsCommand.php` and re-run the import. The mapping only affects `display_name` and `slug` — the raw `county` column is always preserved.
