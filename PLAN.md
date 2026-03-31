# Plan: Lead Intelligence Hub

Extract lead intelligence from the LeadResource "Intelligence" tab into a dedicated **Filament Cluster** with its own sidebar section and per-lead dashboard. Add a new **Website Analysis** tool alongside the existing Prospect Analysis, with results feeding into email generation.

---

**Steps**

### Phase 1 — Database & Models
1. Create `lead_website_analyses` migration with: `lead_id` (FK), `status` (enum: pending/completed/failed), `scraped_data` (JSON — structured extraction), `result` (JSON — AI analysis), `provider`, `model`, `error_message`, `started_at`, `completed_at`
2. Create `LeadWebsiteAnalysis` model mirroring `LeadProspectAnalysis` pattern
3. Add `websiteAnalysis(): HasOne` relationship to `Lead` model
4. Create factory for `LeadWebsiteAnalysis`

### Phase 2 — Website Analysis Job & Service
5. Create `WebsiteScraper` service (`app/Services/Intelligence/WebsiteScraper.php`) — PHP port of key extraction logic from the reference `analyze_prospect.py`: fetch homepage + subpages, extract company name, tech stack (regex signatures), social links, team members, pricing tiers, job postings, contact info, company size signals
6. Create `RunWebsiteAnalysisJob` mirroring `RunProspectAnalysisJob` — calls scraper, sends scraped data to AI with a rich prompt requesting: business_overview, value_proposition, target_market, revenue_model, competitive_position, growth_signals, tech_maturity, sales_angles, pain_points, overall_score
7. Create `WebsiteAnalysisFailedNotification` *(parallel with 5-6)*

### Phase 3 — Intelligence Cluster (Filament) *(depends on Phase 1)*
8. Create `Intelligence` Cluster class at `app/Filament/Clusters/Intelligence.php` — navigation group "Leads", icon `heroicon-o-cpu-chip`
9. Create **Intelligence Dashboard** page — receives lead ID, shows card grid of available tools (Prospect Analysis, Website Analysis, future placeholders) with status/scores
10. Create **Prospect Analysis** detail page — embeds existing `ProspectAnalysis` Livewire component, adds "Run Analysis" action *(parallel with 11)*
11. Create **Website Analysis** detail page + new `WebsiteAnalysis` Livewire component with states: empty → pending (polling) → failed (retry) → completed (rich display of scraped data + AI results)

### Phase 4 — Navigation & Access Points *(depends on Phase 3)*
12. Add Intelligence icon-button per row in `LeadResource` table → links to Intelligence Dashboard
13. Update `ViewLead.php` Intelligence tab — replace inline component with summary + "View Full Intelligence →" link
14. Add bulk "Analyse Website" table action to `LeadResource`

### Phase 5 — Email Generation Integration *(depends on Phase 1-2)*
15. Modify `GenerateColdEmailJob` — when building the user prompt, append available `prospectAnalysis.result` and `websiteAnalysis.result` data (sales_angles, pain_points, opportunity, outreach_strategy) as additional AI context

### Phase 6 — Testing *(parallel with each phase)*
16. Unit tests for `WebsiteScraper` (tech stack detection, extraction helpers)
17. Feature tests for `RunWebsiteAnalysisJob` (success, failure, no-website)
18. Feature tests for Intelligence cluster pages (dashboard, analysis pages, actions)
19. Feature test for email integration (prompt includes analysis when available, works without)

---

**Relevant files**

- `app/Models/Lead.php` — add `websiteAnalysis()` relationship
- `app/Models/LeadProspectAnalysis.php` — pattern to mirror for new model
- `app/Jobs/RunProspectAnalysisJob.php` — pattern to mirror for new job
- `app/Livewire/ProspectAnalysis.php` — pattern for new Livewire component
- `resources/views/livewire/prospect-analysis.blade.php` — view pattern (4 states)
- `app/Filament/Resources/LeadResource.php` — add Intelligence action column + bulk action
- `app/Filament/Resources/LeadResource/Pages/ViewLead.php` — update Intelligence tab
- `app/Jobs/GenerateColdEmailJob.php` — inject analysis context into prompt
- `app/Services/Ai/AiProviderFactory.php` — `makeWithFallback()` for AI calls
- `app/Providers/Filament/AdminPanelProvider.php` — may need to register cluster/navigation group

**New files:** `LeadWebsiteAnalysis` model, `WebsiteScraper` service, `RunWebsiteAnalysisJob`, `WebsiteAnalysisFailedNotification`, Intelligence cluster class, 3 cluster pages, `WebsiteAnalysis` Livewire component + blade view, factory, and ~4 test files.

---

**Verification**

1. `php artisan migrate` — new table created
2. `php artisan test --compact --filter=WebsiteScraper` — scraper extraction tests pass
3. `php artisan test --compact --filter=RunWebsiteAnalysis` — job flow tests pass
4. `php artisan test --compact --filter=Intelligence` — cluster page tests pass
5. `php artisan test --compact --filter=GenerateColdEmail` — email prompt integration pass
6. `vendor/bin/pint --dirty --format agent` — code style clean
7. Manual: Lead table → Intelligence icon → Dashboard → trigger both analyses → view results → generate email → verify richer prompt context

---

**Decisions**

- Filament Cluster approach — Intelligence gets its own sidebar section, scoped per lead via URL parameter
- Separate models per tool — `LeadProspectAnalysis` unchanged, new `LeadWebsiteAnalysis` follows identical pattern; future tools add their own models
- All-in-one AI website analysis — PHP scraper extracts structured data, single AI call produces business intelligence
- Analysis auto-injected into email generation — no user toggle needed (if data exists, it's used)
- Existing `ProspectAnalysis` Livewire component reused inside the new cluster page
- **Excluded from scope:** geo optimisation, competitor tools (future additions using same pattern)

**Further Considerations**

1. **Cluster URL structure:** Filament clusters typically create flat URLs (`/app/intelligence/...`). The lead ID needs to be passed as a route parameter. We may need custom page routing like `/app/intelligence/{lead}/dashboard` — need to verify Filament v5 cluster support for parameterised pages.
2. **Bulk website analysis:** Should we add a bulk "Analyse Websites" table action alongside the existing bulk prospect analysis? Recommend yes, same pattern.
3. **Scraper rate limiting:** Add 200-500ms delays between subpage fetches and a 10s per-page timeout to avoid getting blocked. Skip robots.txt checking for MVP.
