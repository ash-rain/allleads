# Playbooks Design

**Date:** 2026-04-19  
**Status:** Approved

## Overview

Extract saved lead filter presets into database-driven "Playbooks" — named, reusable filter configurations that can be quickly applied from a dropdown above any lead-filtered view, without touching the full filter form.

## Data Model

### `playbooks` table

| column | type | notes |
|---|---|---|
| `id` | bigint PK | |
| `business_id` | FK → businesses | multi-tenant scoped |
| `name` | string | e.g. "Web Dev Prospects" |
| `description` | string nullable | shown as tooltip/subtitle |
| `icon` | string nullable | heroicon name |
| `filters` | JSON | discovery criteria |
| `sort_order` | int default 0 | controls dropdown order |
| `is_active` | bool default true | hide without deleting |
| `timestamps` | | |

### `filters` JSON shape

```json
{
  "no_website": true,
  "has_email": false,
  "rating_min": 4.5,
  "categories": ["restaurant", "cafe"],
  "tags": [1, 3]
}
```

Each key is optional — only present keys are applied. Adding new criteria in the future requires no schema change.

**Excluded intentionally:** `review_count` is not stored on leads and must not be added. Review count filtering only applies to prospecting discovery, not playbooks.

### `Playbook` model

- Casts `filters` as array
- `applyToQuery(Builder $query): Builder` — iterates filters and applies each condition
- Calls existing `Lead` model scopes where available (e.g. `scopeNoWebsite`, `scopeHighRating`)
- Scoped to current business via global scope or explicit `where('business_id', ...)`

## HasPlaybooks Trait

`app/Traits/HasPlaybooks.php` — used by any Filament table page that wants playbook support.

**Provides:**
- `public ?int $selectedPlaybookId = null` — reactive Livewire property
- `getPlaybookOptions(): array` — loads active playbooks for current business
- `applyPlaybook(?int $id): void` — sets `$selectedPlaybookId`, resets table pagination
- Hooks into `modifyQueryUsing` — when a playbook is selected, calls `Playbook::find($id)->applyToQuery($query)`

**Adoption (one line per page):**
```php
use App\Traits\HasPlaybooks;

class ListLeads extends ListRecords
{
    use HasPlaybooks;
}
```

Same for the Prospecting page and any future lead-filtered view.

## Dropdown UI

`resources/views/components/playbook-selector.blade.php` — a select input wired to `applyPlaybook()`, rendered above the table via the page's header area. Shows "— No playbook —" as the default (clears filter). Active playbook name is shown when one is selected.

## Filament Management UI

**`PlaybookResource`** at `app/Filament/Resources/PlaybookResource.php` — full CRUD.

**Form fields:**
- `name` — TextInput, required
- `description` — TextInput, nullable
- `icon` — TextInput (heroicon name), nullable
- `sort_order` — numeric TextInput, default 0
- `is_active` — Toggle
- Filter criteria (serialised to/from `filters` JSON, never raw JSON exposed to user):
  - `no_website` — Toggle
  - `has_email` — Toggle
  - `rating_min` — numeric TextInput (0–5, step 0.1), nullable
  - `categories` — multi-select from distinct lead categories
  - `tags` — multi-select from Tag model

**Table columns:** name (with icon), description, active badge, sort order, filter summary (e.g. "No website · Rating ≥ 4.5").

**Navigation:** same nav group as Leads (`nav_group_leads`), sorted after existing resources.

## Seeded Default Playbooks

| Name | Criteria |
|---|---|
| Web Dev Prospects | no website + rating ≥ 4.5 |
| High-Value No Website | no website + rating ≥ 4.0 |
| Email-Ready Leads | has email + rating ≥ 4.0 |
| Quick Wins | has email + no website + rating ≥ 4.5 |
| Local Gems | rating ≥ 4.8 |
| Contactable Leads | has email |
| No Online Presence | no website |

## Files Added / Changed

| Action | Path |
|---|---|
| Add | `database/migrations/..._create_playbooks_table.php` |
| Add | `app/Models/Playbook.php` |
| Add | `app/Traits/HasPlaybooks.php` |
| Add | `app/Filament/Resources/PlaybookResource.php` |
| Add | `app/Filament/Resources/PlaybookResource/Pages/` |
| Add | `database/seeders/PlaybookSeeder.php` |
| Add | `database/factories/PlaybookFactory.php` |
| Add | `resources/views/components/playbook-selector.blade.php` |
| Modify | `app/Filament/Resources/LeadResource/Pages/ListLeads.php` — add `use HasPlaybooks` |
| Modify | `app/Filament/Pages/Prospecting.php` — add `use HasPlaybooks` |
| Remove | Hardcoded `web_dev_prospects` toggle filter from `LeadResource` |
| Remove | `scopeWebDevProspects` from `Lead` model |

## Testing

- **Feature:** `PlaybookResource` CRUD — create, edit, toggle active
- **Feature:** `HasPlaybooks` on `ListLeads` — setting `selectedPlaybookId` correctly filters table records (via `livewire()->set()`)
- **Feature:** each seeded playbook's `applyToQuery()` returns the correct lead subset
- **Feature:** clearing playbook (null) returns unfiltered results
- Replace any existing `web_dev_prospects` filter tests with the playbook equivalent
