# Lead Discovery & Prospecting — Feature Spec

## Problem

AllLeads users need leads but don't have spreadsheets lying around. They have a **territory** (geography) and a **target** (business category). The current import flow requires them to find leads elsewhere, export a CSV, and upload it. That's friction that kills adoption — especially for solo operators on the $49/mo plan who just want to say "find me dentists near Manchester."

## Solution

A **Prospecting Session** feature that lets users discover local businesses from free data sources (Foursquare, OpenStreetMap), preview results on a map + list, select the ones they want, and import them straight into the existing lead pipeline.

## Non-Goals (v1)

- Google Places API integration (future BYOK premium source)
- Yelp, Apollo, Clearbit, Hunter.io enrichment (future phases)
- Automatic re-prospecting / new business notifications
- Visual automation builder (ArtisanFlow) integration

---

## Data Model

### `territories` table

Saved geographic areas that users prospect repeatedly.

```
id                  - bigint, PK
business_id         - FK → businesses (cascadeOnDelete)
name                - string (e.g. "Central Manchester")
latitude            - decimal(10, 7)
longitude           - decimal(10, 7)
radius_km           - decimal(5, 1) (e.g. 5.0, 10.0, 25.0)
created_by          - FK → users (nullOnDelete)
timestamps
softDeletes
```

**Index:** `(business_id, name)` unique

### `prospecting_sessions` table

A single discovery run against a territory + category.

```
id                  - bigint, PK
business_id         - FK → businesses (cascadeOnDelete)
territory_id        - FK → territories (cascadeOnDelete)
uuid                - uuid, unique
search_query        - string (the category/keyword, e.g. "dentist")
filters             - json, nullable (min_rating, max_rating, has_website, has_phone, min_reviews, max_reviews)
status              - enum: pending, searching, completed, failed
sources_used        - json (array of source identifiers, e.g. ["foursquare", "osm"])
result_count        - unsignedInteger, default 0
imported_count      - unsignedInteger, default 0
dismissed_count     - unsignedInteger, default 0
searched_at         - datetime, nullable
created_by          - FK → users (nullOnDelete)
timestamps
```

**Index:** `(business_id, territory_id)`, `(status)`

### `prospecting_results` table

Individual businesses found during a session — the "staging area" before import.

```
id                  - bigint, PK
prospecting_session_id - FK → prospecting_sessions (cascadeOnDelete)
source              - enum: foursquare, osm, google_places
source_id           - string, nullable (external ID from the source)
title               - string
category            - string, nullable
address             - string, nullable
latitude            - decimal(10, 7), nullable
longitude           - decimal(10, 7), nullable
phone               - string(50), nullable
website             - string, nullable
email               - string, nullable
review_rating       - decimal(2, 1), nullable
review_count        - unsignedInteger, nullable
raw_data            - json, nullable (full API response for this result)
status              - enum: new, selected, dismissed, imported, duplicate
lead_id             - FK → leads, nullable (set when imported)
timestamps
```

**Indexes:** `(prospecting_session_id, status)`, `(source, source_id)` unique

---

## Source Provider Architecture

A contract that any data source implements. Ship with Foursquare + OSM, add Google Places later.

### Interface: `ProspectingSource`

```php
namespace App\Contracts;

interface ProspectingSource
{
    /** Unique identifier for this source, e.g. 'foursquare' */
    public function identifier(): string;

    /** Human-readable name, e.g. 'Foursquare Places' */
    public function name(): string;

    /** Whether this source requires a user-provided API key */
    public function requiresApiKey(): bool;

    /** Search for businesses matching the given criteria */
    public function search(ProspectingSearchCriteria $criteria): ProspectingSearchResult;
}
```

### Value Object: `ProspectingSearchCriteria`

```php
namespace App\DataTransferObjects;

class ProspectingSearchCriteria
{
    public function __construct(
        public string $query,        // "dentist", "plumber", etc.
        public float $latitude,
        public float $longitude,
        public float $radiusKm,
        public ?float $minRating = null,
        public ?float $maxRating = null,
        public ?int $minReviews = null,
        public ?int $maxReviews = null,
        public ?bool $hasWebsite = null,
        public ?bool $hasPhone = null,
    ) {}
}
```

### Value Object: `ProspectingSearchResult`

```php
namespace App\DataTransferObjects;

class ProspectingSearchResult
{
    public function __construct(
        /** @var array<NormalisedBusiness> */
        public array $businesses,
        public int $totalFound,
        public string $source,
        public ?string $nextPageToken = null,
    ) {}
}
```

### Value Object: `NormalisedBusiness`

```php
namespace App\DataTransferObjects;

class NormalisedBusiness
{
    public function __construct(
        public string $title,
        public ?string $sourceId,
        public ?string $category = null,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $email = null,
        public ?float $reviewRating = null,
        public ?int $reviewCount = null,
        public ?array $rawData = null,
    ) {}
}
```

### Source Implementations

**`FoursquareSource`** — Default, free tier (200k calls/mo). Searches via `places/search` endpoint. Returns name, category, address, phone, website, rating. Best coverage for restaurants, retail, services.

**`OpenStreetMapSource`** — Free, no key. Queries Overpass API by `amenity`/`shop`/`office` tags + bounding box. Good for UK/EU coverage. Less structured data — may lack phone/website. Acts as a supplement to fill gaps Foursquare misses.

### Source Manager

```php
namespace App\Services\Prospecting;

class SourceManager
{
    /** @return array<ProspectingSource> */
    public function enabledSources(Business $business): array;

    /** Run search across all enabled sources and merge/dedup results */
    public function search(
        Business $business,
        ProspectingSearchCriteria $criteria
    ): MergedSearchResult;
}
```

The `SourceManager` runs all enabled sources, then deduplicates results by matching on: exact phone number OR (fuzzy title similarity > 85% AND address within 100m). When merging, it keeps the richest record (most fields populated) and notes which sources contributed.

---

## UI Flow

### 1. Territory Management

**Location:** Filament resource — `TerritoryResource`

Simple CRUD. Users create territories with a name, location (geocoded from text input via Nominatim), and radius. The form shows a Leaflet map preview that updates as they type the location and adjust the radius slider.

Fields:
- **Name** — text input, required
- **Location** — text input with geocoding (type "Manchester" → picks coordinates). Debounced Nominatim lookup on blur.
- **Radius** — range slider, 1–50 km, default 10 km
- **Map preview** — Leaflet map showing the circle. Updates reactively via Livewire.

### 2. New Prospecting Session

**Location:** Filament custom page — `ProspectingPage`

**Step 1 — Configure Search**

- **Territory** — select from saved territories (with "quick create" inline modal)
- **Search query** — text input ("dentist", "web design agency", "accountant")
- **Filters** (collapsible, all optional):
  - Min/max rating (0–5 range)
  - Min/max review count
  - Has website (yes/no/any)
  - Has phone (yes/no/any)
- **"Search" button** — dispatches `RunProspectingSearch` job

**Step 2 — Review Results (the main event)**

Split-panel layout:

```
┌─────────────────────────────────────────────────────────────┐
│  Search: "dentist" in Central Manchester (5km)    [Refine]  │
│  Found: 47 results from Foursquare + OSM                    │
├──────────────────────────┬──────────────────────────────────┤
│                          │                                  │
│                          │  ☐ Manchester Dental Care        │
│                          │    ★ 4.6 (128 reviews)          │
│      LEAFLET MAP         │    📍 12 Deansgate, M3 2BY      │
│                          │    🌐 manchesterdental.co.uk     │
│    (pins for each        │    ⚡ No HTTPS · Site 3yr old    │
│     result, colour-      │                                  │
│     coded by status)     │  ☑ Smile Studios MCR             │
│                          │    ★ 3.2 (14 reviews)           │
│                          │    📍 45 Oxford Rd, M1 6FQ       │
│                          │    🌐 No website detected        │
│                          │    ⚡ Great prospect!             │
│                          │                                  │
│                          │  ☒ CityDent (dismissed)          │
│                          │    ★ 4.9 (412 reviews)          │
│                          │    📍 8 King St, M2 6AQ          │
│                          │    ⚡ Already a lead             │
│                          │                                  │
├──────────────────────────┴──────────────────────────────────┤
│  Bulk: [Select All] [Select No-Website] [Select Low-Rating] │
│        [Dismiss All] [Clear Selection]                       │
│                                                              │
│  Selected: 12 leads    [Import Selected →]                   │
└──────────────────────────────────────────────────────────────┘
```

**Map behaviour:**
- Pins colour-coded: blue (new/unreviewed), green (selected), red (dismissed), grey (already imported / duplicate)
- Click a pin → highlights the corresponding list item and scrolls to it
- Click a list item → pans map to that pin
- Cluster pins when zoomed out (Leaflet.markercluster)

**List item details:**
- Business name, rating, review count
- Address
- Website (or "No website detected")
- Quick-signal line from pre-scoring: "No HTTPS", "Site looks outdated", "Only 3 reviews", "Already in your leads" — lightweight checks, not full AI analysis
- Three-state toggle: unmarked / selected / dismissed

**Bulk actions** — the real time-savers:
- "Select all without a website" — for web dev agencies
- "Select all under 4 stars" — for reputation management
- "Select all with < 20 reviews" — for marketing agencies
- "Dismiss already imported" — auto-dismiss anything that matches existing leads

**Step 3 — Import**

Clicking "Import Selected" opens a confirmation modal:

- How many leads will be imported
- Assign to (user select, default: self)
- Apply tags (tag multi-select)
- "Import" button → creates an `ImportBatch`, feeds selected `ProspectingResult` records through the existing `LeadImportPipeline`, marks them as `imported`, sets the `lead_id` FK

The import reuses the existing pipeline entirely — normalization, dedup against existing leads, batch tracking, progress updates. No new import logic needed.

### 3. Session History

**Location:** Filament resource — `ProspectingSessionResource`

List of past sessions showing: territory name, search query, date, result count, imported count. Users can re-open a completed session to see what they imported, what they dismissed, and pick up any they skipped. They can also "re-run" a session to refresh results against the same territory + query.

---

## Pre-Scoring Signals (Lightweight)

Before import, show quick signals next to each result. These are NOT the full AI analysis — just fast checks that help users pick leads:

| Signal | Source | Logic |
|--------|--------|-------|
| "No website" | Search result | `website` field is null |
| "No HTTPS" | Quick URL check | Website starts with `http://` not `https://` |
| "Low reviews" | Search result | `review_count < 20` |
| "Low rating" | Search result | `review_rating < 3.5` |
| "Already a lead" | DB check | Phone or title+address matches existing lead |
| "New business" | Search result | Foursquare `createdAt` within last 6 months |

These are computed during the search job and stored in the `raw_data` JSON as a `signals` array. No external API calls needed.

---

## Jobs & Queue Architecture

### `RunProspectingSearch` (queued)

1. Set session status → `searching`
2. Build `ProspectingSearchCriteria` from session
3. Call `SourceManager::search()` → get merged results
4. For each `NormalisedBusiness`:
   - Check for duplicates against existing leads (reuse `LeadImportPipeline` dedup logic)
   - Compute pre-scoring signals
   - Create `ProspectingResult` record (status: `new`, or `duplicate` if already a lead)
5. Update session: `result_count`, `status → completed`, `searched_at`
6. Broadcast event for Livewire UI to refresh

### `ImportProspectingResults` (queued)

1. Create `ImportBatch` linked to the session
2. Convert selected `ProspectingResult` records to the row format `LeadImportPipeline` expects
3. Run through existing pipeline (normalization, validation, dedup, create/update)
4. Update each `ProspectingResult`: status → `imported`, set `lead_id`
5. Update session: `imported_count`

---

## Technical Decisions

**Map library:** Leaflet + OpenStreetMap tiles. Free, well-supported, lightweight. Use `leaflet` npm package + a Livewire component that dispatches/listens for events (pin clicks, selection changes).

**Geocoding:** Nominatim (free) for territory creation. One request per territory save, cached. Respect 1 req/sec limit.

**Foursquare API:** Free developer tier. Store API key in `config/services.php` as a platform-level key (not BYOK for free sources). Paginate results — Foursquare returns max 50 per request, use cursor pagination to fetch up to 200.

**OSM Overpass API:** No key needed. Build Overpass QL queries mapping the user's search terms to OSM tags. Cache common category→tag mappings (e.g. "dentist" → `amenity=dentist`, "restaurant" → `amenity=restaurant`).

**Dedup across sources:** Run in `SourceManager` after merging. Phone match is exact (after normalisation). Title similarity uses Levenshtein distance (PHP native `similar_text` with 85% threshold). Address proximity uses Haversine formula on coordinates (< 100m = same place).

**Real-time updates:** Broadcast session status changes via Laravel Events so the Livewire page updates automatically when the search job completes. No polling needed — use `wire:poll` as fallback for non-websocket setups.

---

## Migration Path for Future Sources

The `ProspectingSource` interface is the extension point. Adding Google Places later means:

1. Create `GooglePlacesSource implements ProspectingSource`
2. Add `google_places` to the `source` enum on `prospecting_results`
3. Add API key field to Business settings (BYOK)
4. Register in `SourceManager` — it automatically includes it in searches
5. Show estimated API cost in the search confirmation step

Same pattern for Yelp, industry directories, or any future source.

---

## Scope Estimate

| Component | Effort |
|-----------|--------|
| Migrations (3 tables) | Small |
| Models + relationships | Small |
| DTOs + ProspectingSource interface | Small |
| FoursquareSource implementation | Medium |
| OpenStreetMapSource implementation | Medium |
| SourceManager (search + merge + dedup) | Medium |
| RunProspectingSearch job | Medium |
| ImportProspectingResults job | Small (reuses pipeline) |
| Territory Filament resource | Small |
| Prospecting page (map + list + selection) | Large |
| Leaflet Livewire component | Medium |
| ProspectingSession Filament resource | Small |
| Pre-scoring signal computation | Small |
| Tests | Medium |

**Total estimate:** ~2-3 weeks of focused dev time for a solo developer.
