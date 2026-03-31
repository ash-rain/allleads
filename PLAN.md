# Plan: Lead Intelligence Hub

Extract lead intelligence from the LeadResource "Intelligence" tab into a dedicated **Filament Cluster** with its own sidebar section and per-lead dashboard. Add a new **Website Analysis** tool alongside the existing Prospect Analysis, with results feeding into email generation.

---

**Status:** Planning Complete — Ready for Implementation

---

**Steps**

### Phase 1 — Database & Models

**1. Create `lead_website_analyses` migration**

```bash
php artisan make:migration create_lead_website_analyses_table --no-interaction
```

**Schema:**
- `id` (bigInt, PK)
- `lead_id` (FK → leads.id, cascade delete)
- `status` (enum: pending/completed/failed, default: pending)
- `scraped_data` (JSON, nullable) — structured extraction from website
- `result` (JSON, nullable) — AI analysis output
- `provider` (string, nullable) — AI provider used
- `model` (string, nullable) — AI model used
- `error_message` (text, nullable)
- `started_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `created_at` / `updated_at` (timestamps)

**Index:** `lead_id` (unique) — one analysis per lead

**2. Create `LeadWebsiteAnalysis` model**

**File:** `app/Models/LeadWebsiteAnalysis.php`

**Pattern to mirror:** `app/Models/LeadProspectAnalysis.php`

**Key differences from ProspectAnalysis:**
- Add `scraped_data` cast as array
- Same status constants (pending/completed/failed)
- Same relationship to Lead

**3. Add relationship to `Lead` model**

**File:** `app/Models/Lead.php`

```php
public function websiteAnalysis(): HasOne
{
    return $this->hasOne(LeadWebsiteAnalysis::class);
}
```

**4. Create factory**

**File:** `database/factories/LeadWebsiteAnalysisFactory.php`

**States:**
- `pending()` — status pending, no result
- `completed()` — status completed with sample result data
- `failed()` — status failed with error message

### Phase 2 — Website Analysis Job & Service

**5. Create `WebsiteScraper` service**

**File:** `app/Services/Intelligence/WebsiteScraper.php`

**Namespace:** `App\Services\Intelligence`

**Responsibilities:**
- Fetch homepage + key subpages (/about, /team, /pricing, /careers, /contact)
- Extract structured data using regex patterns and DOM parsing
- Rate limiting: 200-500ms delays between requests, 10s timeout per page

**Extraction targets:**
- `company_name` — from title tag, OG tags, or h1
- `tech_stack` — detect CMS/frameworks (WordPress, Shopify, React, etc.)
- `social_links` — LinkedIn, Twitter, Facebook, Instagram URLs
- `team_members` — names and titles from /team or /about
- `pricing_tiers` — pricing page content, plan names, price points
- `job_postings` — open positions from /careers or /jobs
- `contact_info` — emails, phones, addresses from /contact
- `company_size_signals` — employee count mentions, "we're a team of X"

**Return format:** Array with extracted fields, null for missing data

**6. Create `RunWebsiteAnalysisJob`**

**File:** `app/Jobs/RunWebsiteAnalysisJob.php`

**Pattern to mirror:** `app/Jobs/RunProspectAnalysisJob.php`

**Key differences:**
- Calls `WebsiteScraper` instead of simple HTTP fetch
- Stores `scraped_data` JSON before AI call
- Richer AI prompt requesting structured business intelligence

**AI Prompt outputs:**
- `business_overview` — 2-3 sentence company summary
- `value_proposition` — what they sell and to whom
- `target_market` — customer segments they serve
- `revenue_model` — how they make money
- `competitive_position` — market position vs competitors
- `growth_signals` — expansion indicators (hiring, new products)
- `tech_maturity` — digital sophistication assessment
- `sales_angles` — 3 specific outreach angles
- `pain_points` — likely challenges we can solve
- `overall_score` — 1-100 fit score for our services

**7. Create `WebsiteAnalysisFailedNotification`**

**File:** `app/Notifications/WebsiteAnalysisFailedNotification.php`

**Pattern:** Mirror `app/Notifications/ProspectAnalysisFailedNotification.php`

**Channels:** database (in-app), mail (optional)

### Phase 3 — Intelligence Cluster (Filament) *(depends on Phase 1)*

**8. Create `Intelligence` Cluster class**

**File:** `app/Filament/Clusters/Intelligence.php`

**Configuration:**
- Navigation group: "Leads"
- Icon: `heroicon-o-cpu-chip`
- Label: "Intelligence"

**URL Structure Challenge:**
Filament clusters create flat URLs by default. We need parameterized URLs:
- `/app/intelligence/{lead}/dashboard`
- `/app/intelligence/{lead}/prospect-analysis`
- `/app/intelligence/{lead}/website-analysis`

**Solution:** Override `getUrl()` in cluster class to accept lead parameter. Pages will use route parameters.

**9. Create Intelligence Dashboard page**

**File:** `app/Filament/Clusters/Intelligence/Pages/IntelligenceDashboard.php`

**Features:**
- Receives `lead` route parameter
- Shows card grid of available intelligence tools
- Each card displays: tool name, status badge, score (if completed), last run date
- Cards link to detail pages
- "Run All Analyses" bulk action

**Tool cards:**
- Prospect Analysis (existing)
- Website Analysis (new)
- Placeholder cards for future tools (Geo Optimisation, Competitor Analysis)

**10. Create Prospect Analysis detail page**

**File:** `app/Filament/Clusters/Intelligence/Pages/ProspectAnalysisPage.php`

**Features:**
- Embeds existing `App\Livewire\ProspectAnalysis` component
- Adds "Run Analysis" action button
- Breadcrumb: Intelligence → Dashboard → Prospect Analysis
- Back button to Dashboard

**11. Create Website Analysis detail page + Livewire component**

**Page file:** `app/Filament/Clusters/Intelligence/Pages/WebsiteAnalysisPage.php`

**Livewire component:** `app/Livewire/WebsiteAnalysis.php`

**View file:** `resources/views/livewire/website-analysis.blade.php`

**Component states:**
1. **Empty** — no analysis exists → "Start Analysis" button
2. **Pending** — analysis running → polling spinner, "Analysis in progress..."
3. **Failed** — error occurred → error message, "Retry" button
4. **Completed** — rich display:
   - Overall score badge
   - Business overview section
   - Sales angles (actionable)
   - Pain points we can solve
   - Tech stack detected
   - Raw scraped data (collapsible)

### Phase 4 — Navigation & Access Points *(depends on Phase 3)*

**12. Add Intelligence icon-button to LeadResource table**

**File:** `app/Filament/Resources/LeadResource.php`

**Location:** Table columns (action column)

**Implementation:**
```php
Tables\Columns\IconColumn::make('intelligence')
    ->label('')
    ->icon('heroicon-o-cpu-chip')
    ->url(fn (Lead $record) => Intelligence::getUrl(['lead' => $record->id]))
    ->openUrlInNewTab(false);
```

**13. Update ViewLead Intelligence tab**

**File:** `app/Filament/Resources/LeadResource/Pages/ViewLead.php`

**Changes:**
- Replace inline `ProspectAnalysis` Livewire component
- Show summary cards: Prospect Analysis status/score, Website Analysis status/score
- "View Full Intelligence →" button linking to Intelligence Dashboard
- Keep tab for quick glance, defer details to cluster

**14. Add bulk "Analyse Website" table action**

**File:** `app/Filament/Resources/LeadResource.php`

**Location:** Table bulk actions

**Implementation:**
```php
Actions\BulkAction::make('analyse_websites')
    ->label(__('leads.action_analyse_websites'))
    ->icon('heroicon-o-globe-alt')
    ->requiresConfirmation()
    ->action(function (Collection $records): void {
        foreach ($records as $lead) {
            if ($lead->website) {
                RunWebsiteAnalysisJob::dispatch($lead, auth()->id());
            }
        }
        Notification::make()->title(...)->success()->send();
    });
```

**Filter:** Only enable for leads with websites (disable otherwise with `->deselectRecordsAfterCompletion()`)

### Phase 5 — Email Generation Integration *(depends on Phase 1-2)*

**15. Modify `GenerateColdEmailJob`**

**File:** `app/Jobs/GenerateColdEmailJob.php`

**Changes to `buildUserPrompt()`:**

Load analysis data and append to prompt:

```php
private function buildUserPrompt(Lead $lead, AiSetting $setting): string
{
    $parts = ["Business name: {$lead->title}"];
    
    // ... existing lead data ...
    
    // Load prospect analysis if available
    if ($lead->prospectAnalysis?->status === LeadProspectAnalysis::STATUS_COMPLETED) {
        $result = $lead->prospectAnalysis->result;
        $parts[] = "\nProspect Analysis:";
        $parts[] = "- Opportunity: {$result['opportunity']}";
        $parts[] = "- Outreach Strategy: {$result['outreach_strategy']}";
    }
    
    // Load website analysis if available
    if ($lead->websiteAnalysis?->status === LeadWebsiteAnalysis::STATUS_COMPLETED) {
        $result = $lead->websiteAnalysis->result;
        $parts[] = "\nWebsite Analysis:";
        $parts[] = "- Business Overview: {$result['business_overview']}";
        $parts[] = "- Sales Angles: " . implode(', ', $result['sales_angles']);
        $parts[] = "- Pain Points: " . implode(', ', $result['pain_points']}";
    }
    
    return 'Write a cold email for this lead:' . "\n" . implode("\n", $parts);
}
```

**Behavior:**
- Analysis data auto-injected if available (no user toggle)
- Works without analysis (backward compatible)
- Richer context = more personalized emails
- Sales angles directly inform email angle selection

### Phase 6 — Testing *(parallel with each phase)*

**16. Unit tests for `WebsiteScraper`**

**File:** `tests/Unit/Services/Intelligence/WebsiteScraperTest.php`

**Test cases:**
- `it_detects_wordpress_from_generator_meta()`
- `it_detects_shopify_from_cdn_urls()`
- `it_extracts_social_links_from_footer()`
- `it_parses_pricing_tiers_from_pricing_page()`
- `it_handles_missing_website_gracefully()`
- `it_respects_rate_limiting_between_requests()`
- `it_extracts_company_name_from_title_tag()`

**17. Feature tests for `RunWebsiteAnalysisJob`**

**File:** `tests/Feature/Jobs/RunWebsiteAnalysisJobTest.php`

**Test cases:**
- `it_creates_pending_analysis_on_dispatch()`
- `it_scrapes_website_and_calls_ai_provider()`
- `it_stores_scraped_data_and_ai_result()`
- `it_marks_failed_on_scraper_exception()`
- `it_marks_failed_on_ai_provider_error()`
- `it_skips_analysis_for_lead_without_website()`
- `it_sends_notification_on_failure()`

**18. Feature tests for Intelligence cluster pages**

**File:** `tests/Feature/Filament/IntelligenceClusterTest.php`

**Test cases:**
- `it_displays_dashboard_with_tool_cards()`
- `it_shows_prospect_analysis_status_on_dashboard()`
- `it_shows_website_analysis_status_on_dashboard()`
- `it_can_run_prospect_analysis_from_detail_page()`
- `it_can_run_website_analysis_from_detail_page()`
- `it_polls_pending_analysis_status()`
- `it_displays_completed_analysis_results()`

**19. Feature test for email integration**

**File:** `tests/Feature/Jobs/GenerateColdEmailJobTest.php` (add to existing)

**Test cases:**
- `it_includes_prospect_analysis_in_prompt_when_available()`
- `it_includes_website_analysis_in_prompt_when_available()`
- `it_generates_email_without_analysis_when_none_exists()`
- `it_prioritizes_sales_angles_from_analysis()`

---

**Relevant Files Reference**

### Pattern Files (to mirror)

| New File | Pattern Source |
|----------|---------------|
| `app/Models/LeadWebsiteAnalysis.php` | `app/Models/LeadProspectAnalysis.php` |
| `app/Jobs/RunWebsiteAnalysisJob.php` | `app/Jobs/RunProspectAnalysisJob.php` |
| `app/Notifications/WebsiteAnalysisFailedNotification.php` | `app/Notifications/ProspectAnalysisFailedNotification.php` |
| `app/Livewire/WebsiteAnalysis.php` | `app/Livewire/ProspectAnalysis.php` |
| `resources/views/livewire/website-analysis.blade.php` | `resources/views/livewire/prospect-analysis.blade.php` |
| `database/factories/LeadWebsiteAnalysisFactory.php` | `database/factories/LeadProspectAnalysisFactory.php` (if exists) |

### Files to Modify

| File | Changes |
|------|---------|
| `app/Models/Lead.php` | Add `websiteAnalysis(): HasOne` relationship |
| `app/Filament/Resources/LeadResource.php` | Add Intelligence icon column, bulk website analysis action |
| `app/Filament/Resources/LeadResource/Pages/ViewLead.php` | Update Intelligence tab with summary + link |
| `app/Jobs/GenerateColdEmailJob.php` | Inject prospect + website analysis into prompt |

### New Files to Create

**Models & Database:**
- `database/migrations/xxxx_create_lead_website_analyses_table.php`
- `app/Models/LeadWebsiteAnalysis.php`
- `database/factories/LeadWebsiteAnalysisFactory.php`

**Services:**
- `app/Services/Intelligence/WebsiteScraper.php`

**Jobs:**
- `app/Jobs/RunWebsiteAnalysisJob.php`

**Notifications:**
- `app/Notifications/WebsiteAnalysisFailedNotification.php`

**Filament Cluster:**
- `app/Filament/Clusters/Intelligence.php`
- `app/Filament/Clusters/Intelligence/Pages/IntelligenceDashboard.php`
- `app/Filament/Clusters/Intelligence/Pages/ProspectAnalysisPage.php`
- `app/Filament/Clusters/Intelligence/Pages/WebsiteAnalysisPage.php`

**Livewire:**
- `app/Livewire/WebsiteAnalysis.php`
- `resources/views/livewire/website-analysis.blade.php`

**Tests:**
- `tests/Unit/Services/Intelligence/WebsiteScraperTest.php`
- `tests/Feature/Jobs/RunWebsiteAnalysisJobTest.php`
- `tests/Feature/Filament/IntelligenceClusterTest.php`

**Total new files:** ~15
**Total modified files:** ~4

---

**Verification Steps**

### Automated Verification

| Step | Command | Expected Result |
|------|---------|-----------------|
| 1 | `php artisan migrate` | `lead_website_analyses` table created |
| 2 | `php artisan test --compact --filter=WebsiteScraper` | All 7 scraper tests pass |
| 3 | `php artisan test --compact --filter=RunWebsiteAnalysis` | All 7 job tests pass |
| 4 | `php artisan test --compact --filter=Intelligence` | All 7 cluster tests pass |
| 5 | `php artisan test --compact --filter=GenerateColdEmail` | Email integration tests pass |
| 6 | `vendor/bin/pint --dirty --format agent` | No style violations |
| 7 | `make analyse` | Larastan level 8 passes |

### Manual Verification Checklist

**Navigation & Access:**
- [ ] Lead table shows Intelligence icon (cpu-chip) per row
- [ ] Clicking Intelligence icon navigates to `/app/intelligence/{lead}/dashboard`
- [ ] ViewLead Intelligence tab shows summary cards + "View Full Intelligence" link

**Intelligence Dashboard:**
- [ ] Dashboard displays Prospect Analysis card with correct status
- [ ] Dashboard displays Website Analysis card with correct status
- [ ] Cards show scores when analyses are completed
- [ ] Cards link to detail pages

**Prospect Analysis Detail:**
- [ ] Page embeds existing ProspectAnalysis Livewire component
- [ ] "Run Analysis" button triggers job
- [ ] Pending state shows polling spinner
- [ ] Completed state shows full results

**Website Analysis Detail:**
- [ ] Empty state shows "Start Analysis" button
- [ ] Pending state shows polling spinner
- [ ] Failed state shows error + "Retry" button
- [ ] Completed state shows: score, business overview, sales angles, pain points, tech stack
- [ ] Collapsible section shows raw scraped data

**Bulk Actions:**
- [ ] Lead table bulk action "Analyse Websites" available
- [ ] Action only processes leads with websites
- [ ] Notification sent on completion

**Email Integration:**
- [ ] Generate email for lead with both analyses → prompt includes both
- [ ] Generate email for lead with no analyses → prompt works normally
- [ ] Generated email references sales angles from analysis

**Performance:**
- [ ] Website scraper respects rate limiting (200-500ms delays)
- [ ] Analysis pages poll efficiently (5-second intervals)
- [ ] No N+1 queries on dashboard

---

**Architectural Decisions**

### 1. Filament Cluster Approach
**Decision:** Extract Intelligence into a dedicated Filament Cluster with per-lead scoping.

**Rationale:**
- Intelligence tools are conceptually separate from basic lead management
- Cluster provides room for expansion (more analysis tools)
- Per-lead scoping via URL parameter (`/intelligence/{lead}/...`) keeps context clear
- Sidebar navigation group "Leads" keeps it discoverable

**Trade-offs:**
- Requires custom URL handling (Filament clusters default to flat URLs)
- Additional navigation step vs inline tab

### 2. Separate Models Per Analysis Tool
**Decision:** Create `LeadWebsiteAnalysis` as separate model, mirroring `LeadProspectAnalysis`.

**Rationale:**
- Each analysis has distinct data structure (prospect vs website)
- Independent lifecycle (can re-run one without affecting other)
- Clear separation of concerns
- Future tools follow same pattern

**Rejected Alternative:** Single `lead_analyses` table with polymorphic or type column — too complex for current needs.

### 3. PHP-Based Website Scraping
**Decision:** Port key extraction logic from Python to PHP, keep within `WebsiteScraper` service.

**Rationale:**
- No external service dependencies (Python runtime, Scrapy, etc.)
- Simpler deployment (single PHP runtime)
- Laravel HTTP client provides sufficient functionality
- Regex + DOM parsing sufficient for MVP

**Limitations:**
- Less sophisticated than browser-based scraping
- JavaScript-rendered content may be missed
- Rate limiting required to avoid blocks

### 4. All-in-One AI Analysis
**Decision:** Single AI call with rich prompt vs. chained specialized calls.

**Rationale:**
- Simpler architecture (one job, one prompt)
- Faster execution (single API round-trip)
- Sufficient for business intelligence extraction
- Easier to maintain one prompt

**Prompt outputs:**
- `business_overview`, `value_proposition`, `target_market`
- `revenue_model`, `competitive_position`, `growth_signals`
- `tech_maturity`, `sales_angles` (array), `pain_points` (array)
- `overall_score` (1-100)

### 5. Auto-Inject Analysis into Email Generation
**Decision:** Automatically include available analysis data in email prompts, no user toggle.

**Rationale:**
- Zero friction — if data exists, it's used
- Backward compatible — works without analysis
- Sales angles directly inform email personalization
- No UI complexity (toggles, checkboxes)

**Data included:**
- Prospect Analysis: `opportunity`, `outreach_strategy`
- Website Analysis: `business_overview`, `sales_angles`, `pain_points`

### 6. Reuse Existing ProspectAnalysis Component
**Decision:** Keep existing `ProspectAnalysis` Livewire component, embed in cluster page.

**Rationale:**
- Minimizes code duplication
- Existing component already handles 4 states (empty, pending, failed, completed)
- Only need to wrap with cluster navigation

### 7. Rate Limiting in Scraper
**Decision:** Add 200-500ms delays between subpage fetches, 10s timeout per page.

**Rationale:**
- Prevents IP blocking
- Respects target websites
- Sufficient for typical business sites
- Skip robots.txt checking for MVP (add later if needed)

### 8. Excluded from Scope
**Not included in this plan:**
- **Geo Optimisation Tool** — analyze local SEO, map presence (future)
- **Competitor Analysis Tool** — identify and analyze competitors (future)
- **Browser-based scraping** — Puppeteer/Playwright (overkill for MVP)
- **Robots.txt checking** — add if compliance issues arise
- **Analysis scheduling** — auto-refresh on schedule (future)

---

**Technical Implementation Notes**

### Cluster URL Structure with Parameters

**Challenge:** Filament v5 clusters default to flat URLs (`/app/intelligence/dashboard`). We need parameterized URLs (`/app/intelligence/{lead}/dashboard`).

**Solution approach:**

```php
// In app/Filament/Clusters/Intelligence.php
public static function getUrl(array $parameters = [], ?string $panel = null): string
{
    $lead = $parameters['lead'] ?? null;
    
    return parent::getUrl($parameters, $panel) . ($lead ? "/{$lead}/dashboard" : '');
}
```

**Page route registration:**

```php
// In IntelligenceDashboard.php
public static function getRoutes(): array
{
    return [
        '{lead}/dashboard' => static::class,
    ];
}

public function mount(int $lead): void
{
    $this->lead = Lead::findOrFail($lead);
}
```

### Database Indexing

**Required indexes:**
- `lead_id` UNIQUE on `lead_website_analyses` (one analysis per lead)
- `status` index for filtering pending/completed/failed
- Composite `(lead_id, status)` for dashboard queries

### Translation Keys Required

**New keys to add to `lang/en/leads.php`:**
```php
'intelligence_nav_label' => 'Intelligence',
'intelligence_dashboard' => 'Intelligence Dashboard',
'action_analyse_websites' => 'Analyse Websites',
'analysis_status_pending' => 'Pending',
'analysis_status_completed' => 'Completed',
'analysis_status_failed' => 'Failed',
'analysis_score' => 'Score: :score',
'analysis_last_run' => 'Last run: :date',
'view_full_intelligence' => 'View Full Intelligence →',
'website_analysis' => 'Website Analysis',
'prospect_analysis' => 'Prospect Analysis',
'run_analysis' => 'Run Analysis',
'retry_analysis' => 'Retry',
'analysis_in_progress' => 'Analysis in progress...',
'no_analysis_yet' => 'No analysis yet',
```

**New keys for `lang/en/notifications.php`:**
```php
'website_analysis_failed_title' => 'Website Analysis Failed',
'website_analysis_failed_body' => 'Failed to analyse website for :lead. Error: :error',
```

### Queue Configuration

**Job configuration:**
- `RunWebsiteAnalysisJob`: `tries=2`, `timeout=120` (scraping + AI call)
- Queue: `default` (can be moved to `intelligence` queue if volume grows)

### Caching Strategy

**No caching required for MVP:**
- Analysis results stored in database
- Livewire polling handles pending state
- Dashboard queries are simple (single row lookups)

**Future optimization:**
- Cache scraped data for 24h if re-analysis needed
- Cache AI results if model unchanged

### Error Handling

**Scraper errors (caught, logged, job continues):**
- Timeout → null content for that page
- 404 → skip subpage
- SSL error → try http fallback

**AI errors (fail job, notify user):**
- Rate limit → retry with exponential backoff
- Invalid JSON → fail with parse error
- Provider error → fail with provider message

### Security Considerations

**Scraper safety:**
- Only fetch HTTP/HTTPS URLs (validate scheme)
- Respect timeouts (prevent slowloris)
- No authentication cookies sent
- User-Agent identifies bot: `AllLeads-Analyzer/1.0`

**Authorization:**
- Cluster pages use Filament's built-in auth
- Users can only access leads they have permission to view
- Bulk actions respect existing policies

### Performance Targets

**Scraper:**
- Homepage fetch: < 5s
- Subpage fetches: < 3s each (parallel if possible)
- Total scrape time: < 15s for typical site

**AI call:**
- Response time: < 30s (depends on provider)
- Token usage: ~2000 output tokens

**Dashboard:**
- Page load: < 200ms
- Poll interval: 5 seconds (configurable)

### Future Extension Points

**New analysis tools (same pattern):**
1. Create model mirroring `LeadProspectAnalysis`
2. Create job calling new service
3. Add card to IntelligenceDashboard
4. Create detail page
5. Add to email prompt integration

**Analysis scheduling:**
- Add `scheduled_analysis_at` to models
- Create `ScheduleAnalysisJob` using Laravel scheduler
- Allow weekly/monthly re-analysis

**Analysis comparison:**
- Store historical analysis versions
- Show trends over time
- Track score changes

**Export functionality:**
- Export analysis results to PDF
- Bulk export for reporting
- API endpoint for external integrations
