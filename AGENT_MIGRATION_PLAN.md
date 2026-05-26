# Intelligence Agents Migration Plan

Migrate AllLeads intelligence tools from the current Job + Service + custom AI provider architecture to Laravel 13 AI SDK Agents.

**Goal:** Eliminate boilerplate across 8 job classes and 3 services, replace the custom AI provider stack with Laravel's built-in provider system, and add a lightweight Filament-based agent management dashboard.

**Approach:** Incremental migration — each phase leaves the app fully functional. No big-bang rewrite.

---

## Current Architecture (What We're Replacing)

```
Job (boilerplate: status tracking, error handling, notifications, JSON parsing)
  → Service (data gathering: HTTP calls, scraping, API queries)
    → AiProviderFactory → FallbackAiProvider → [OpenRouter|Groq|Gemini]Provider
      → Manual JSON parsing (strip <think> blocks, markdown fences)
        → Model::create(result)
```

**Files to be removed or gutted by the end:**

- `app/Services/Ai/AiProviderInterface.php`
- `app/Services/Ai/AiProviderFactory.php`
- `app/Services/Ai/AbstractOpenAiCompatibleProvider.php`
- `app/Services/Ai/OpenRouterProvider.php`
- `app/Services/Ai/GroqProvider.php`
- `app/Services/Ai/GeminiProvider.php`
- `app/Services/Ai/FallbackAiProvider.php`
- `app/Services/Ai/AiProviderException.php`
- `app/Services/Ai/RateLimitException.php`

**Files to be substantially refactored:**

- All 8 job classes in `app/Jobs/`
- All 3 services in `app/Services/Intelligence/`
- `config/ai.php`

---

## Target Architecture

```
Job (thin: dispatch agent, store result, log activity)
  → Agent (instructions + schema + tools)
    → Tool classes (extracted from current Services)
      → Laravel AI SDK provider (configured via Lab enum)
        → HasStructuredOutput (typed JSON, no manual parsing)
          → Model::create(result)
```

---

## Phase 0: Foundation

**Install the AI SDK and configure providers. Nothing changes for users yet.**

### 0.1 — Install Laravel AI SDK

```bash
composer require laravel/ai
php artisan vendor:publish --tag=ai-config
php artisan migrate  # creates agent_conversations + agent_conversation_messages tables
```

### 0.2 — Configure Providers

Update `.env` with provider credentials for the AI SDK. The SDK uses its own config (`config/ai.php` from the SDK), so the existing custom `config/ai.php` won't conflict yet — but rename ours to `config/ai-legacy.php` to avoid future confusion.

```env
# Laravel AI SDK providers
AI_PROVIDER=groq
OPENROUTER_API_KEY=...
GROQ_API_KEY=...
GEMINI_API_KEY=...
```

Map existing providers to the SDK's `Lab` enum:
- `Lab::Groq` — primary (was default)
- `Lab::Gemini` — fallback
- OpenRouter — check if the SDK supports it natively or if a custom driver is needed. If not supported, keep a thin OpenRouter adapter or switch to one of the natively supported providers.

### 0.3 — Create Directory Structure

```
app/Ai/
├── Agents/
│   ├── Concerns/
│   │   └── TracksAgentRuns.php        # Shared status/logging logic
│   ├── TrendAnalysisAgent.php
│   ├── GeoAnalysisAgent.php
│   ├── WebsiteAnalysisAgent.php
│   ├── ProspectScoringAgent.php
│   ├── ColdEmailAgent.php
│   └── DraftRefinementAgent.php
└── Tools/
    ├── Trend/
    │   ├── SearchReddit.php
    │   ├── SearchHackerNews.php
    │   ├── SearchPolymarket.php
    │   └── SearchGoogleNews.php
    ├── Geo/
    │   ├── FetchRobotsTxt.php
    │   ├── FetchLlmsTxt.php
    │   ├── CheckAiCrawlerAccess.php
    │   ├── AnalyzeCitability.php
    │   ├── CheckBrandMentions.php
    │   ├── ExtractSchemaMarkup.php
    │   └── CheckTechnicalSeo.php
    ├── Website/
    │   ├── ScrapePage.php
    │   ├── DetectTechStack.php
    │   ├── ExtractSocialLinks.php
    │   ├── ExtractTeamMembers.php
    │   ├── ExtractPricingTiers.php
    │   ├── ExtractJobPostings.php
    │   └── ExtractContactInfo.php
    └── Email/
        ├── FetchLeadAnalyses.php
        └── FetchPreviousDrafts.php
```

### 0.4 — Create the `TracksAgentRuns` Concern

This trait holds the shared logic currently duplicated across all jobs: status tracking, timing, error recording, and activity logging.

```php
// app/Ai/Agents/Concerns/TracksAgentRuns.php

trait TracksAgentRuns
{
    protected function markPending(Model $analysis): void;
    protected function markCompleted(Model $analysis, array $result, string $provider, string $model): void;
    protected function markFailed(Model $analysis, string $error): void;
    protected function recordActivity(Lead $lead, string $type, array $properties = []): void;
}
```

### Acceptance Criteria

- [ ] `php artisan ai:test` (or equivalent) confirms SDK is installed and can reach at least one provider
- [ ] Directory structure created
- [ ] `TracksAgentRuns` trait written
- [ ] Existing functionality unchanged — all current jobs still work via the legacy provider stack

---

## Phase 1: Extract Tools from Services

**Break the three monolithic services into small, single-responsibility Tool classes. The services still work during this phase — tools are new code alongside them.**

Each Tool class follows the Laravel AI SDK tool pattern (check `php artisan make:tool` for the exact scaffold). A tool is essentially: a name, a description (for the LLM), input parameters, and an `execute()` method that returns data.

### 1.1 — Trend Tools (from `TrendResearcher`)

Extract each API call into its own tool:

| Tool | Source Method | External API |
|------|--------------|-------------|
| `SearchReddit` | `searchReddit()` | `reddit.com/search.json` |
| `SearchHackerNews` | `searchHackerNews()` | `hn.algolia.com/api/v1/search` |
| `SearchPolymarket` | `searchPolymarket()` | `gamma-api.polymarket.com/events` |
| `SearchGoogleNews` | `searchGoogleNews()` | `news.google.com/rss/search` |

Each tool accepts `topic` (string) and `days` (int, default 30) as parameters. Returns the same array structure the current service methods return.

### 1.2 — GEO Tools (from `GeoAnalyzer`)

| Tool | Source Method | What It Does |
|------|--------------|-------------|
| `FetchRobotsTxt` | `fetchRobotsTxt()` | HTTP GET, parse robots.txt directives |
| `FetchLlmsTxt` | `fetchLlmsTxt()` | HTTP GET, check for llms.txt presence |
| `CheckAiCrawlerAccess` | `checkAiCrawlers()` | Parse robots.txt for 14 AI crawler rules |
| `AnalyzeCitability` | `analyzeCitability()` | 5-factor scoring algorithm on page content |
| `CheckBrandMentions` | `checkBrandMentions()` | Wikipedia + Wikidata API queries |
| `ExtractSchemaMarkup` | `extractSchemaMarkup()` | Parse JSON-LD from HTML |
| `CheckTechnicalSeo` | `checkTechnicalSeo()` | Check meta tags, canonical, viewport, HTTPS, H1 |

### 1.3 — Website Tools (from `WebsiteScraper`)

| Tool | Source Method | What It Does |
|------|--------------|-------------|
| `ScrapePage` | `scrapePage()` | Fetch + clean HTML for a single URL |
| `DetectTechStack` | `detectTechStack()` | Regex/header-based detection of 12 technologies |
| `ExtractSocialLinks` | `extractSocialLinks()` | Parse href patterns for social platforms |
| `ExtractTeamMembers` | `extractTeamMembers()` | Parse /about and /team pages |
| `ExtractPricingTiers` | `extractPricingTiers()` | Parse /pricing page structure |
| `ExtractJobPostings` | `extractJobPostings()` | Parse /careers and /jobs pages |
| `ExtractContactInfo` | `extractContactInfo()` | Extract emails, phones from /contact |

### 1.4 — Email Tools

| Tool | What It Does |
|------|-------------|
| `FetchLeadAnalyses` | Load completed analyses (prospect, website, trend, GEO) for a lead |
| `FetchPreviousDrafts` | Load prior email drafts for context/style consistency |

### Acceptance Criteria

- [ ] All tool classes created with proper input schemas and descriptions
- [ ] Each tool is independently testable — `(new SearchReddit)->execute(['topic' => 'web design', 'days' => 30])`
- [ ] Existing services untouched — old jobs still work

---

## Phase 2: Create Agent Classes

**Build the agent classes that compose the tools. Each agent defines its instructions, tools, and structured output schema.**

### 2.1 — TrendAnalysisAgent

```php
class TrendAnalysisAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private Lead|string $subject,
        private ?Business $business = null,
    ) {}

    public function instructions(): string
    {
        // Current system prompt from RunTrendAnalysisJob, lines ~80-120
        // Include business context via $this->business?->toPromptContext()
    }

    public function tools(): iterable
    {
        return [
            new SearchReddit,
            new SearchHackerNews,
            new SearchPolymarket,
            new SearchGoogleNews,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'market_overview'      => $schema->string()->required(),
            'trending_topics'      => $schema->array()->required(),
            'community_sentiment'  => $schema->string()->required(),
            'opportunities'        => $schema->array()->required(),
            'talking_points'       => $schema->array()->required(),
            'prediction_markets'   => $schema->string(),
            'relevance_score'      => $schema->integer()->min(1)->max(100)->required(),
        ];
    }
}
```

### 2.2 — GeoAnalysisAgent

Schema keys: `geo_score`, `ai_visibility_summary`, `citability_assessment`, `crawler_access_summary`, `brand_authority_assessment`, `schema_assessment`, `technical_assessment`, `sales_angles`, `quick_wins`, `platform_recommendations`

Tools: `FetchRobotsTxt`, `FetchLlmsTxt`, `CheckAiCrawlerAccess`, `AnalyzeCitability`, `CheckBrandMentions`, `ExtractSchemaMarkup`, `CheckTechnicalSeo`

### 2.3 — WebsiteAnalysisAgent

Schema keys: `business_overview`, `value_proposition`, `target_market`, `revenue_model`, `competitive_position`, `growth_signals`, `tech_maturity`, `sales_angles`, `pain_points`, `overall_score`

Tools: `ScrapePage`, `DetectTechStack`, `ExtractSocialLinks`, `ExtractTeamMembers`, `ExtractPricingTiers`, `ExtractJobPostings`, `ExtractContactInfo`

### 2.4 — ProspectScoringAgent

Schema keys: `prospect_score`, `company_fit`, `contact_intel`, `opportunity`, `competitive_intel`, `outreach_strategy`

Tools: None initially — this agent works from lead data passed in the prompt. Could later use `FetchLeadAnalyses` to pull in other agents' results.

### 2.5 — ColdEmailAgent

Schema keys: `subject`, `body`

Tools: `FetchLeadAnalyses`, `FetchPreviousDrafts`

Special: Uses business AI settings for tone, language, length, personalisation, opener_style, CTA/PS flags. The `instructions()` method should pull these from the lead's business `AiSetting`.

### 2.6 — DraftRefinementAgent

Schema keys: `body` (just the refined email body)

Tools: None — receives the current draft and instruction directly in the prompt.

### Design Decision: Provider Selection

The agents themselves don't hardcode a provider. The job (or the registry, in Phase 4) passes the provider and model at prompt time:

```php
$agent->prompt($input, provider: Lab::Groq, model: 'llama-3.3-70b-versatile');
```

This keeps agents portable and lets the management layer control routing.

### Design Decision: OpenRouter

If the Laravel AI SDK doesn't natively support OpenRouter, two options:
1. **Drop it** — you're using free-tier models anyway; Groq + Gemini cover your needs
2. **Custom driver** — if the SDK supports custom provider registration, write a thin OpenRouter driver

Recommend option 1 unless there's a specific OpenRouter model you can't get elsewhere.

### Acceptance Criteria

- [ ] All 6 agent classes created via `php artisan make:agent`
- [ ] Each agent can be prompted in Tinker: `(new TrendAnalysisAgent($lead))->prompt('Analyze trends for web design')`
- [ ] Structured output returns typed arrays matching current JSON schemas
- [ ] Existing jobs still work — agents are new code alongside the old system

---

## Phase 3: Rewire Jobs to Use Agents

**Replace the guts of each job with an agent call. This is the phase where boilerplate dies.**

### 3.1 — Migration Pattern

Each job goes from ~100-150 lines to ~20-30 lines:

```php
// Before (RunGeoAnalysisJob — simplified)
public function handle(GeoAnalyzer $geoAnalyzer): void
{
    $analysis = $this->lead->geoAnalysis()->updateOrCreate([...], ['status' => 'pending', ...]);

    $rawData = $geoAnalyzer->analyze($this->lead->website, $this->lead->title);
    $analysis->update(['raw_data' => $rawData]);

    $setting = $this->lead->business?->aiSettingOrCreate() ?? AiSetting::singleton();
    $provider = AiProviderFactory::makeWithFallback($setting);

    $systemPrompt = "You are a GEO readiness analyst..."; // 50+ lines of prompt
    $userPrompt = "Analyze this data: " . json_encode($rawData);

    $response = $provider->complete($systemPrompt, $userPrompt, [
        'temperature' => $setting->temperature,
        'max_tokens' => $setting->max_tokens,
        'timeout' => 120,
    ]);

    // Strip <think> blocks, markdown fences, parse JSON, validate keys...
    $result = $this->parseJson($response);

    $analysis->update(['status' => 'completed', 'result' => $result, ...]);
    $this->recordActivity($this->lead, 'geo_analysis_completed', [...]);
}

// After
public function handle(): void
{
    $analysis = $this->lead->geoAnalysis()->updateOrCreate([...], ['status' => 'pending', ...]);

    try {
        $agent = new GeoAnalysisAgent($this->lead, $this->lead->business);
        $result = $agent->prompt(
            "Analyze {$this->lead->website} for GEO readiness",
            provider: AgentRegistry::providerFor(GeoAnalysisAgent::class),
            model: AgentRegistry::modelFor(GeoAnalysisAgent::class),
            timeout: 180,
        );

        $analysis->update([
            'status' => 'completed',
            'result' => $result->toArray(),
            'provider' => $result->provider ?? 'unknown',
            'model' => $result->model ?? 'unknown',
            'completed_at' => now(),
        ]);

        $this->recordActivity($this->lead, 'geo_analysis_completed', [
            'geo_score' => $result['geo_score'],
        ]);
    } catch (\Throwable $e) {
        $analysis->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        $this->recordActivity($this->lead, 'geo_analysis_failed');
        throw $e;
    }
}
```

### 3.2 — Job Migration Order

Migrate one at a time, test, then move to the next:

1. **ProspectScoringAgent** — simplest (no tools, no raw data gathering). Good proof of concept.
2. **DraftRefinementAgent** — also simple (no tools). Validates the structured output flow.
3. **TrendAnalysisAgent** — first agent with tools. Validates the tool-calling loop.
4. **WebsiteAnalysisAgent** — more tools, validates scraping via agent tools.
5. **GeoAnalysisAgent** — most complex tool set.
6. **ColdEmailAgent** — depends on results from other agents, validates cross-agent data flow.

### 3.3 — Handle Company-Level Jobs

`RunCompanyTrendAnalysisJob` and `RunCompanyGeoAnalysisJob` use the same agents but with a string topic/URL instead of a Lead. The agents already accept `Lead|string` — these jobs just call the agent differently and store to `TrendAnalysis`/`GeoAnalysis` instead of the lead-scoped models.

### 3.4 — Preserve the `<think>` Block Stripping

If using reasoning models (e.g., DeepSeek R1 via OpenRouter), the `<think>` block stripping might still be needed. Check if the AI SDK handles this natively. If not, add a small middleware or post-processor on the agent. This is a minor detail but easy to miss.

### Acceptance Criteria

- [ ] All 8 jobs migrated to use agents
- [ ] Each job is under 30 lines of business logic
- [ ] All Filament pages (TrendAnalysisPage, GeoAnalysisPage, etc.) still work — they dispatch the same jobs
- [ ] Run `make test` — all existing tests pass
- [ ] Manual smoke test: run each analysis type on a real lead

---

## Phase 4: Agent Registry (Lightweight Management)

**A Filament resource for viewing and configuring agents. Not a no-code builder — just a dashboard for ops.**

### 4.1 — Migration & Model

```bash
php artisan make:model AgentRegistryEntry -m
```

```php
// Migration
Schema::create('agent_registry', function (Blueprint $table) {
    $table->id();
    $table->string('agent_class')->unique();       // App\Ai\Agents\GeoAnalysisAgent
    $table->string('name');                         // "GEO Analysis"
    $table->string('description')->nullable();      // "Analyzes GEO readiness..."
    $table->boolean('enabled')->default(true);
    $table->string('provider')->nullable();         // Override: 'groq', 'gemini', etc.
    $table->string('model')->nullable();            // Override: 'llama-3.3-70b-versatile'
    $table->text('prompt_suffix')->nullable();       // Appended to agent instructions
    $table->json('options')->nullable();             // temperature, max_tokens, timeout
    $table->unsignedInteger('cooldown_seconds')->default(0);  // Min time between runs per lead
    $table->timestamps();
});
```

### 4.2 — AgentRegistry Service

```php
// app/Ai/AgentRegistry.php

class AgentRegistry
{
    // Resolve provider for an agent, falling back to defaults
    public static function providerFor(string $agentClass): Lab;

    // Resolve model for an agent, falling back to defaults
    public static function modelFor(string $agentClass): string;

    // Check if an agent is enabled
    public static function isEnabled(string $agentClass): bool;

    // Get prompt suffix (appended to agent instructions)
    public static function promptSuffixFor(string $agentClass): ?string;

    // Get merged options (temperature, max_tokens, timeout)
    public static function optionsFor(string $agentClass): array;

    // Check cooldown for a specific lead
    public static function canRunFor(string $agentClass, Lead $lead): bool;

    // Seed the registry from discovered agent classes
    public static function sync(): void;
}
```

The `sync()` method scans `app/Ai/Agents/` for classes implementing `Agent`, and creates/updates registry entries. Run via `php artisan agents:sync` or automatically on deploy.

### 4.3 — Agent Run Log

```bash
php artisan make:model AgentRun -m
```

```php
Schema::create('agent_runs', function (Blueprint $table) {
    $table->id();
    $table->string('agent_class');
    $table->nullableMorphs('subject');  // Lead, Business, or null
    $table->foreignId('user_id')->constrained();
    $table->string('provider')->nullable();
    $table->string('model')->nullable();
    $table->string('status');           // pending, completed, failed
    $table->unsignedInteger('duration_ms')->nullable();
    $table->unsignedInteger('input_tokens')->nullable();
    $table->unsignedInteger('output_tokens')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();
});
```

Update the `TracksAgentRuns` trait to write here automatically on every agent invocation.

### 4.4 — Filament Resource

```bash
php artisan make:filament-resource AgentRegistryEntry
```

**List Page:**
- Table columns: Name, Provider, Model, Enabled (toggle), Last Run (from agent_runs), Success Rate (computed)
- Bulk action: enable/disable
- Filter: by provider, by enabled status

**Edit Page:**
- Toggle enabled/disabled
- Provider dropdown (from Lab enum + "default")
- Model text input (or dropdown populated from provider)
- Prompt suffix textarea
- Temperature slider (0.0-1.0)
- Max tokens input
- Timeout input
- Cooldown input

**Agent Run Log (relation manager or separate resource):**
- Table: Agent, Subject (lead link), Status, Provider, Model, Duration, Tokens, Created At
- Filterable by agent, status, date range
- Click through to see full result (if stored)

### 4.5 — Artisan Commands

```bash
php artisan agents:sync        # Discover and register agent classes
php artisan agents:list        # Show all registered agents with status
php artisan agents:run GeoAnalysisAgent --lead=123  # Manual trigger from CLI
```

### Acceptance Criteria

- [ ] Registry seeded with all 6 agents via `agents:sync`
- [ ] Filament resource shows all agents with correct defaults
- [ ] Changing provider/model in the UI affects the next agent run
- [ ] Disabling an agent prevents jobs from running it (with graceful skip, not error)
- [ ] Agent run log captures every invocation with timing and token counts
- [ ] `make test` passes

---

## Phase 5: Clean Up

**Delete the old provider stack and legacy config. The satisfying phase.**

### 5.1 — Delete Custom AI Provider Stack

Remove these files entirely:
- `app/Services/Ai/AiProviderInterface.php`
- `app/Services/Ai/AiProviderFactory.php`
- `app/Services/Ai/AbstractOpenAiCompatibleProvider.php`
- `app/Services/Ai/OpenRouterProvider.php`
- `app/Services/Ai/GroqProvider.php`
- `app/Services/Ai/GeminiProvider.php`
- `app/Services/Ai/FallbackAiProvider.php`
- `app/Services/Ai/AiProviderException.php`
- `app/Services/Ai/RateLimitException.php`

### 5.2 — Delete or Archive Original Services

The intelligence services (`TrendResearcher`, `GeoAnalyzer`, `WebsiteScraper`) are now fully replaced by Tool classes. Delete them:
- `app/Services/Intelligence/TrendResearcher.php`
- `app/Services/Intelligence/GeoAnalyzer.php`
- `app/Services/Intelligence/WebsiteScraper.php`

### 5.3 — Clean Up Config

Remove `config/ai-legacy.php` (the renamed original config). The AI SDK's own config handles provider settings now.

### 5.4 — Update AiSetting Model

The `AiSetting` model currently stores per-business provider preferences and API keys. Decide:
- **Option A:** Keep it for per-business provider/model overrides, but wire it through the AgentRegistry
- **Option B:** Merge its concerns into the AgentRegistry (per-business overrides become a column on `agent_registry`)

Recommend **Option A** — AiSetting handles *business* preferences (tone, language, temperature), AgentRegistry handles *agent* configuration (provider, model, enabled). They're orthogonal concerns.

### 5.5 — Remove Dead Imports

Grep for references to deleted classes and clean them up:
```bash
grep -r "AiProviderFactory\|AiProviderInterface\|FallbackAiProvider\|OpenRouterProvider\|GroqProvider\|GeminiProvider" app/
```

### Acceptance Criteria

- [ ] `app/Services/Ai/` directory is empty or removed
- [ ] `app/Services/Intelligence/` directory is empty or removed
- [ ] No references to deleted classes anywhere in `app/`
- [ ] `make test` passes
- [ ] `make analyse` (Larastan) passes with no new errors

---

## Phase 6: Polish the Filament UX

**Update the existing Filament intelligence pages to use the agent system more naturally.**

### 6.1 — Unified "Run Analysis" Action

Currently each analysis page (TrendAnalysisPage, GeoAnalysisPage, etc.) has its own "Run Analysis" button. Add a dropdown action on the Lead ViewLead page:

```
[Run Intelligence ▾]
├── Prospect Analysis
├── Website Analysis
├── GEO Analysis
├── Trend Analysis
└── Run All
```

"Run All" dispatches all enabled agents for that lead in sequence (or parallel if you're feeling bold). Each checks the AgentRegistry for enabled status and cooldown.

### 6.2 — Agent Status Indicators

On the lead detail page, show a small status row for each agent:

```
🟢 Prospect Analysis — Score: 78 — 2 hours ago
🟢 GEO Analysis — Score: 62 — 2 hours ago
🟡 Trend Analysis — Running...
⚪ Website Analysis — Not run
```

### 6.3 — Agent Run History Widget

A small Filament widget on the dashboard showing recent agent runs across all leads:

```
Last 24h: 47 runs | 42 completed | 3 failed | 2 running
Top errors: RateLimitException (2), Timeout (1)
```

### Acceptance Criteria

- [ ] Dropdown action works on lead detail page
- [ ] "Run All" respects enabled/disabled and cooldown
- [ ] Status indicators update via polling
- [ ] Dashboard widget shows meaningful data

---

## Estimated Effort

| Phase | Scope | Estimate |
|-------|-------|----------|
| Phase 0 | Foundation | Half a day |
| Phase 1 | Extract Tools | 1-2 days |
| Phase 2 | Create Agents | 1 day |
| Phase 3 | Rewire Jobs | 1-2 days |
| Phase 4 | Agent Registry | 1-2 days |
| Phase 5 | Clean Up | Half a day |
| Phase 6 | Filament Polish | 1 day |
| **Total** | | **~6-8 days** |

---

## Risk Register

| Risk | Impact | Mitigation |
|------|--------|------------|
| AI SDK doesn't support OpenRouter natively | Medium — free models become unavailable | Drop OpenRouter or write a custom driver. Groq free tier covers the same use case. |
| Agent tool-calling loop is slower than direct service calls | Low — adds 1-2 extra LLM round-trips | For time-critical agents, pre-gather data in the job and pass it in the prompt instead of using tools. Keep tools for agents that benefit from dynamic decision-making. |
| Structured output schema validation differences | Medium — current JSON parsing is lenient, SDK may be strict | Test each agent's output against production data. Add fallback parsing if the SDK's structured output fails on edge cases. |
| AiSetting per-business overrides don't mesh with AgentRegistry | Low | AgentRegistry is the default; AiSetting overrides at prompt time. Clear precedence: Business AiSetting > AgentRegistry > SDK defaults. |
| Existing tests break during migration | Medium | Migrate one job at a time. Run `make test` after each. Keep old code until the new path is validated. |

---

## Verification Checklist (Post-Migration)

Run after Phase 5, before considering this done:

- [ ] `make test` — full test suite passes
- [ ] `make analyse` — Larastan reports no new errors
- [ ] `make lint` — Pint finds no style issues in new code
- [ ] Smoke test each agent via Tinker
- [ ] Smoke test each analysis type via the Filament UI on a real lead
- [ ] Verify agent run log captures all runs with timing
- [ ] Verify disabling an agent in the registry prevents execution
- [ ] Verify changing provider/model in the registry affects the next run
- [ ] Check `grep -r "AiProviderFactory\|TrendResearcher\|GeoAnalyzer\|WebsiteScraper" app/` returns nothing
- [ ] Check no secrets or API keys in the agent registry table
