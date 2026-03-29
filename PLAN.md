# AllLeads CRM — Implementation Roadmap

## Overview

A single-tenant, feature-rich CRM for a web-development agency to manage, qualify, and cold-outreach local business leads. The app imports leads from CSV or JSON, surfaces high-potential prospects (rating > 4.5, no website), generates AI-crafted cold emails via free cloud LLMs, and tracks conversations with each lead through a Gmail-like threaded view. Emails are sent and (optionally) received via Brevo.

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Booster | Boost (Laravel Boost / Precognition helpers) |
| Admin UI | Filament 5 |
| Reactive UI | Livewire 4 |
| Styling | Tailwind CSS 4 |
| Internationalisation | Laravel `lang/` + `__()` helper, EN default (extensible) |
| PWA | Web App Manifest + Service Worker (offline shell cache) |
| Database | MySQL 8 |
| Queue | Laravel Queues (database driver, MySQL-backed `jobs` table) |
| AI | OpenRouter · Groq · Google Gemini (free tiers) |
| Email sending | Brevo (Sendinblue SMTP + API) |
| Email inbound | Brevo Inbound Webhook |
| Auth | Laravel Fortify / Filament auth |
| Roles | Spatie Laravel Permission (Admin, Agent) |
| Containerisation | Docker + Docker Compose |
| CI/CD | GitHub Actions |
| Production | DigitalOcean Kubernetes (per-namespace: staging, production) |

---

## Domain Model

```
users               — staff accounts (Admin / Agent)
leads               — imported business contacts
  lead_tags (pivot) — many-to-many tags
tags                — reusable labels
lead_notes          — manual notes and call logs per lead
lead_activities     — immutable timeline events per lead
email_campaigns     — batches of selected leads queued for outreach
email_drafts        — per-lead AI-generated draft (editable, previewed before send)
email_threads       — conversation container per lead
email_messages      — individual messages within a thread (outbound + inbound)
ai_settings         — global AI provider / model / style configuration
user_email_settings — per-user sender name, address, CC, signature, header
```

### Lead fields (from import)
- `title` — business name
- `category` — business type
- `address` — full address string
- `phone`
- `website` — nullable; absence is the primary qualifier
- `email` — nullable
- `review_rating` — decimal
- `status` — enum: `new | contacted | replied | closed | disqualified`
- `assignee_id` — FK → users
- `import_batch` — UUID grouping per import run
- `source` — `csv | json | manual`

---

## Phases

---

### Phase 1 — Project Scaffold & Infrastructure

**Goals:** Runnable local dev environment; CI pipeline skeleton; K8s manifests skeleton.

#### 1.1 Laravel 13 + Filament 5 + Livewire 4 bootstrap
- [ ] `laravel new allleads --pest` with Filament 5 installer
- [ ] Install: `filament/filament`, `livewire/livewire`, `spatie/laravel-permission`, `league/csv`, `protonemedia/laravel-form-components`
- [ ] Configure Tailwind 4 with Filament preset

#### 1.6 Internationalisation (i18n)
> **Rule: no hardcoded text anywhere in views, Filament resources, Livewire components, or validation messages. Every visible string must go through `__()`.**

- [ ] Create `lang/en/` with topic-grouped files: `leads.php`, `emails.php`, `ai.php`, `auth.php`, `common.php`, `notifications.php`
- [ ] Configure Filament to load translations from `lang/en/filament/` (override panel labels, action names, table column headers)
- [ ] Add a custom Artisan command `php artisan translations:missing` to scan views for un-translated strings and report gaps
- [ ] Livewire component labels, validation messages, and flash notifications all use lang keys
- [ ] All Filament column headers, action labels, form field labels defined via `->label(__('leads.column_name'))` — never a raw string
- [ ] Seeder strings (tag names, demo data) use English constants from lang files
- [ ] Document the convention in `CONTRIBUTING.md`: _"Run `php artisan translations:missing` before every PR. No raw strings in views."_

#### 1.7 PWA & Responsive Design

**PWA (Progressive Web App)**
- [ ] `public/manifest.json`: `name`, `short_name`, `start_url`, `display: standalone`, `theme_color`, `background_color`, icons at 192×192 and 512×512
- [ ] Service worker (`public/sw.js`): caches app shell (CSS, JS, fonts) on install; serves cached shell on offline; network-first strategy for API/data requests
- [ ] Register service worker from `resources/js/app.js`
- [ ] `<link rel="manifest">` and `<meta name="theme-color">` in the base Blade layout
- [ ] Offline fallback page (`resources/views/offline.blade.php`) — shown when network unavailable and page not cached
- [ ] iOS meta tags: `apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, `apple-touch-icon`
- [ ] App icons generated at all required sizes (192, 512, maskable) and referenced in manifest

**Responsive Design**
- [ ] Mobile-first Tailwind classes throughout — base styles target mobile, `md:` and `lg:` override for wider screens
- [ ] Filament 5 panel: enable sidebar collapse on mobile; use Filament's built-in responsive table behaviour
- [ ] Leads table: card/list layout on mobile (`sm:hidden` table, `sm:block` card grid)
- [ ] Conversation view: single-column on mobile (message list above, composer below); split-pane only on `lg:`
- [ ] Draft editor: stacked (editor then preview) on mobile; side-by-side on `lg:`
- [ ] Dashboard widgets: 1-column grid on mobile → 2-column on `md:` → 4-column on `xl:`
- [ ] Navigation: bottom tab bar on mobile for key sections (Leads, Campaigns, Conversations)

#### 1.2 Docker Compose (local)
```
services:
  app        — PHP 8.4-FPM (Laravel)
  nginx      — reverse proxy
  mysql      — MySQL 8 (mirrors DO Managed MySQL in production)
  queue      — Laravel queue worker (php artisan queue:work)
  scheduler  — cron runner (php artisan schedule:run every minute)
```
- [ ] `.env.example` with all required keys documented
- [ ] `Makefile` with shortcuts: `make up`, `make migrate`, `make seed`, `make test`

#### 1.3 GitHub Actions CI
```yaml
jobs:
  test:    lint (Pint) + Pest suite + static analysis (Larastan)
  build:   Docker image build + push to GHCR
  deploy:  kubectl rollout to k8s namespace (staging on main, production on release tag)
```
- [ ] Secrets: `GHCR_TOKEN`, `DO_KUBECONFIG`, `APP_KEY`, `DB_PASSWORD`, `BREVO_KEY`, `OPENROUTER_KEY`, `GROQ_KEY`, `GEMINI_KEY`

#### 1.4 DigitalOcean K8s manifests
- [ ] Namespaces: `allleads-staging`, `allleads-production`
- [ ] Resources per namespace: `Deployment`, `Service`, `Ingress` (cert-manager TLS), `ConfigMap`, `Secret`, `HorizontalPodAutoscaler`
- [ ] MySQL via DO Managed Database (external) — same engine as local dev, no Redis dependency
- [ ] `k8s/` directory in repo with kustomize overlays for staging vs production

#### 1.5 Seeds & Bootstrap Data
- [ ] `AdminSeeder`: one admin user (`admin@allleads.dev` / configurable via `.env`)
- [ ] `AgentSeeder`: one demo agent user
- [ ] `TagSeeder`: starter tags — `hot-lead`, `no-website`, `high-rating`, `web-dev-prospect`, `called`, `emailed`
- [ ] `DemoLeadSeeder`: 20 realistic leads using the CSV/JSON structure (Faker + realistic Bulgarian business names)
- [ ] `AiSettingsSeeder`: sensible defaults (OpenRouter, `mistralai/mistral-7b-instruct:free`, language: English, style: Professional)

---

### Phase 2 — Lead Management Core

**Goals:** Full CRUD for leads; filtering; tagging; assignee; activity timeline.

#### 2.1 Leads Resource (Filament)
- [ ] `LeadResource` with table columns: name, category, rating (badge), website (icon: ✓/✗), email, status (badge), assignee, tags, created_at
- [ ] Sortable, searchable columns
- [ ] Bulk actions: assign, change status, add tag, delete
- [ ] Detail page: tabbed layout — Overview | Conversation | Notes & Calls | Activity Timeline

#### 2.2 Smart Filters & Default Filter
- [ ] Filter sidebar: rating range, has/no website, has/no email, category, status, tags, assignee, import batch, date range
- [ ] **Saved Filter: "Web Dev Prospects"** — pre-loaded default: `rating > 4.5 AND website IS NULL` — displayed as a named view shortcut in the top nav
- [ ] Filter presets saved per-user in `user_filter_presets` table

#### 2.3 Tags & Status
- [ ] Inline tag management on lead row (Filament tags input)
- [ ] Status transition enforced: `new → contacted → replied → closed` (can always move to `disqualified`)
- [ ] Status change logged to activity timeline automatically

#### 2.4 Notes & Call Logs
- [ ] `LeadNote` model: type (`note | call`), content (rich text), created_by, created_at
- [ ] Filament repeater / timeline component on lead detail page
- [ ] Call log includes optional: duration, outcome dropdown

#### 2.5 Activity Timeline
- [ ] Immutable `LeadActivity` events, auto-recorded on: import, status change, tag add/remove, assignee change, email sent, email received, note added
- [ ] Displayed as vertical timeline on lead detail page (Filament Infolists)

---

### Phase 3 — CSV & JSON Import

**Goals:** Reliable, validated, duplicate-safe bulk import with progress feedback.

#### 3.1 Import UI
- [ ] Filament Action on Leads table: "Import Leads"
- [ ] File upload (CSV or JSON accepted), auto-detected by MIME
- [ ] Optional: tag all imported leads with a custom tag and/or assign to a user

#### 3.2 Import Pipeline
- [ ] `ImportLeadsJob` (queued) using `league/csv` for CSV, `json_decode` for JSON
- [ ] Row validation: required `title`, `review_rating` numeric, URL validation for `website`
- [ ] Duplicate detection: match on `title + address` (fuzzy) or exact `phone`; offer skip / update / create
- [ ] Import batch UUID recorded on each lead for later filtering
- [ ] Progress tracked via `import_batches` table + Livewire polling on the UI

#### 3.3 Import Batches Page
- [ ] List of past imports: filename, date, record count, success/fail/skipped breakdown
- [ ] Ability to undo (soft-delete) an entire batch

---

### Phase 4 — AI Email Generation

**Goals:** Queue bulk email generation jobs; configurable AI providers; per-lead draft output.

#### 4.1 AI Settings Page (Admin only)
A rich, intuitive settings page under **Settings → AI & Email Generation**:

**Provider Configuration** (tabs per provider):
- [ ] **OpenRouter**: API key, model picker (fetched live from OpenRouter `/models` API filtered to `free` tier; falls back to `config/ai.php` list when API unavailable), temperature, max tokens
- [ ] **Groq**: API key, model picker (fetched live from Groq `/models`; falls back to `config/ai.php`), temperature
- [ ] **Google Gemini**: API key, model picker (static list from `config/ai.php` — Gemini has no public model-list endpoint), temperature
- [ ] Active provider selector (radio with provider logos)

**Email Generation Style**:
- [ ] Language: searchable select (English, Bulgarian, German, Spanish, French, + custom)
- [ ] Tone: Professional · Friendly · Casual · Persuasive · Consultative
- [ ] Length: Short (3–4 sentences) · Medium (2 paragraphs) · Long (detailed pitch)
- [ ] Personalisation level: Low (generic) · Medium (uses category) · High (uses category + address + rating context)
- [ ] Opener style: Question · Statement · Compliment · Statistic
- [ ] Include: [ ] Portfolio mention [ ] Free audit offer [ ] Call-to-action button [ ] PS line
- [ ] Custom system prompt override (textarea, shown when "Advanced" toggle is on)
- [ ] Preview prompt: "Show rendered system prompt" button (live preview of the final prompt that will be sent)

**Per-User Overrides**: agents can override language and tone in their own profile settings.

#### 4.2 Campaign Creation (Bulk Email Queue)
- [ ] Select leads from Leads table (checkbox bulk select, or use current filtered view)
- [ ] "Generate Cold Emails" bulk action → opens modal:
  - [ ] Campaign name
  - [ ] AI provider/model (inherits from global settings, overridable per campaign)
  - [ ] Style overrides for this campaign
  - [ ] Estimated cost: "$0 (free tier)" display
- [ ] Confirms → creates `EmailCampaign` record → queues `GenerateColdEmailJob` per lead

#### 4.3 `GenerateColdEmailJob`
- [ ] Builds prompt from: lead data (name, category, address, rating) + AI settings
- [ ] Calls selected provider API (abstracted via `AiProviderInterface` with OpenRouter / Groq / Gemini implementations)
- [ ] Creates `EmailDraft` for the lead (status: `draft`)
- [ ] Creates `EmailThread` + initial `EmailMessage` (role: `ai_draft`)
- [ ] On failure: retry x3, then mark draft as `failed` with error message

#### 4.4 AI Provider Abstraction & Config
```php
interface AiProviderInterface {
    public function complete(string $systemPrompt, string $userPrompt, array $options): string;
    public function availableModels(): array; // live fetch with config fallback
}
// Implementations: OpenRouterProvider, GroqProvider, GeminiProvider
// Resolved via AiProviderFactory based on active setting
```

**`config/ai.php`** — canonical model lists used when live API fetch fails or no API key is set yet:
```php
return [
    'openrouter' => [
        'endpoint' => 'https://openrouter.ai/api/v1',
        'models'   => [
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3.1-8b-instruct:free',
            'google/gemma-2-9b-it:free',
            'microsoft/phi-3-mini-128k-instruct:free',
        ],
    ],
    'groq' => [
        'endpoint' => 'https://api.groq.com/openai/v1',
        'models'   => [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'mixtral-8x7b-32768',
            'gemma2-9b-it',
        ],
    ],
    'gemini' => [
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
        'models'   => [
            'gemini-2.0-flash-lite',
            'gemini-1.5-flash-8b',
            'gemini-1.5-flash',
        ],
    ],
];
```
- [ ] `availableModels()` tries live fetch first, caches result for 1 hour, falls back to `config/ai.php` list on any error
- [ ] Config file is the single source of truth for model IDs — no model strings hardcoded elsewhere in PHP or Blade

---

### Phase 5 — Email Draft Editor & Preview

**Goals:** Rich editing experience before any email is sent; chat-with-AI to refine drafts.

#### 5.1 Draft Editor (Livewire component)
- [ ] Accessed from lead detail page → Conversation tab → Draft card
- [ ] Split-pane layout: **left = editor**, **right = live HTML preview** (rendered in sandboxed iframe)
- [ ] Editor fields: Subject, Body (Trix or Markdown with preview toggle), CC override
- [ ] User email settings auto-populated (sender name, signature, header image)

#### 5.2 Chat-with-Agent on Draft
- [ ] Below the editor: a chat thread (Livewire, streamed responses)
- [ ] User types instructions: *"Make it shorter"*, *"Add a PS about our portfolio"*, *"Translate to Bulgarian"*
- [ ] Each instruction queues an `RefineDraftJob` → AI rewrites → new version appears in editor
- [ ] **Version history**: drafts keep all previous versions (stored as `draft_versions`); user can restore any version

#### 5.3 Draft Actions
- [ ] **Send Now** → moves draft to `queued_for_send`, triggers `SendEmailJob`
- [ ] **Schedule** → datetime picker, sets `send_at`
- [ ] **Discard** → soft-delete draft, keeps thread

---

### Phase 6 — Email Sending & Conversation View

**Goals:** Send via Brevo; receive replies via webhook; threaded conversation UI.

#### 6.1 Brevo Integration
- [ ] `brevo/brevo-php` SDK or direct HTTP via `Illuminate\Http\Client`
- [ ] `SendEmailJob`: sends via Brevo Transactional API, records `message_id` from Brevo response
- [ ] Custom headers: `X-Lead-ID`, `X-Thread-ID` (for reply matching via webhook)
- [ ] Sending from user's configured sender address (with Brevo domain verification reminder in UI)

#### 6.2 Inbound Webhook (Brevo → App)
- [ ] `POST /webhooks/brevo/inbound` — verified via Brevo webhook secret
- [ ] Parses reply, matches to thread via `In-Reply-To` header or `X-Thread-ID`
- [ ] Creates new `EmailMessage` (role: `lead_reply`) on the thread
- [ ] Fires `LeadRepliedEvent` → updates lead status to `replied`, logs to activity timeline, sends in-app notification to assignee

#### 6.3 Manual Reply Entry (fallback)
- [ ] On conversation view: "Log Manual Reply" button
- [ ] Text area for lead's reply content + date picker
- [ ] Creates `EmailMessage` (role: `lead_reply`, source: `manual`)

#### 6.4 Conversation View (Livewire)
- [ ] Gmail-inspired threaded view on lead detail → Conversation tab
- [ ] Messages displayed chronologically: outbound (right-aligned, brand colour) + inbound (left-aligned, neutral)
- [ ] Each message: sender, timestamp, rendered HTML body, expand/collapse
- [ ] **Reply Composer** at the bottom:
  - [ ] Text input for user's reply OR instruction to AI: *"The lead said they're interested, write a follow-up proposing a discovery call"*
  - [ ] Toggle: "Write reply myself" vs "Ask AI to draft reply"
  - [ ] AI draft → opens Draft Editor in modal for review before sending
  - [ ] "Send" button → `SendEmailJob`

#### 6.5 User Email Settings (per user)
- [ ] Profile settings page (Filament): Sender Name, Sender Email Address, Reply-To, CC (default), BCC (default)
- [ ] Email Header: image upload (stored in `storage/app/email-headers`)
- [ ] Signature: rich-text editor with variable support (`{{user_name}}`, `{{user_email}}`, `{{company_name}}`)
- [ ] Preview: rendered signature preview

---

### Phase 7 — Notifications & Dashboard

#### 7.1 In-App Notifications
- [ ] Filament notification bell: new reply received, draft generation failed, import complete
- [ ] Laravel broadcasting via Reverb (or Pusher-compatible) for real-time bell updates

#### 7.2 Dashboard (Filament)
- [ ] Stats widgets: Total Leads, Web Dev Prospects (rating>4.5, no website), Emails Sent Today, Open Threads, Replies Received
- [ ] Chart: leads imported over time (by batch)
- [ ] Chart: email status funnel (draft → sent → replied → closed)
- [ ] Recent activity feed
- [ ] Quick-access: "Web Dev Prospects" filter shortcut card

---

## File / Directory Structure (key additions to Laravel default)

```
app/
  Filament/
    Resources/LeadResource/         — Lead CRUD, filters, bulk actions
    Resources/EmailCampaignResource/
    Pages/Dashboard.php
    Widgets/                        — Stats, charts
  Http/
    Controllers/Webhooks/BrevoInboundController.php
  Jobs/
    ImportLeadsJob.php
    GenerateColdEmailJob.php
    RefineDraftJob.php
    SendEmailJob.php
  Services/
    Ai/
      AiProviderInterface.php
      OpenRouterProvider.php
      GroqProvider.php
      GeminiProvider.php
      AiProviderFactory.php
    Import/
      CsvLeadImporter.php
      JsonLeadImporter.php
      LeadImportPipeline.php
    Brevo/
      BrevoMailService.php
      BrevoInboundParser.php
  Models/
    Lead.php, Tag.php, LeadNote.php, LeadActivity.php
    EmailCampaign.php, EmailDraft.php, EmailDraftVersion.php
    EmailThread.php, EmailMessage.php
    AiSetting.php, UserEmailSetting.php, ImportBatch.php
  Events/
    LeadRepliedEvent.php
    LeadImportedEvent.php
  Listeners/ …
  Policies/ …  (LeadPolicy, EmailDraftPolicy)
config/
  ai.php                            — provider endpoints + fallback model lists
database/
  migrations/
  seeders/
    AdminSeeder.php
    AgentSeeder.php
    TagSeeder.php
    DemoLeadSeeder.php
    AiSettingsSeeder.php
lang/
  en/
    auth.php
    common.php
    leads.php
    emails.php
    ai.php
    notifications.php
  en/filament/                      — Filament panel label overrides
public/
  manifest.json                     — PWA manifest
  sw.js                             — service worker
  icons/                            — PWA icons (192, 512, maskable)
resources/
  views/offline.blade.php           — PWA offline fallback
docker/
  nginx/default.conf
  php/Dockerfile
  php/php.ini
k8s/
  base/
    deployment-app.yaml, deployment-queue.yaml, deployment-scheduler.yaml
    service.yaml, ingress.yaml, configmap.yaml, hpa.yaml
  overlays/
    staging/kustomization.yaml
    production/kustomization.yaml
tests/
  Unit/
    Services/           — AI providers, importers, Brevo parser
    Models/             — Lead, EmailThread, etc.
  Feature/
    Import/             — CSV/JSON import pipeline
    Webhooks/           — Brevo inbound + event webhook
    Api/                — all HTTP endpoints
  Livewire/             — Livewire component tests
.github/
  workflows/
    ci.yml      (test + lint + static analysis)
    deploy.yml  (build + push + kubectl rollout)
```

---

## K8s Deployment Strategy

- [ ] **Staging** namespace (`allleads-staging`): auto-deploys on push to `main`
- [ ] **Production** namespace (`allleads-production`): deploys on GitHub Release tag (`v*.*.*`)
- [ ] Rolling update strategy; readiness probe on `/up`
- [ ] Queue workers as a separate `Deployment` (`php artisan queue:work --sleep=3 --tries=3`)
- [ ] Scheduler as a separate `Deployment` running a loop (`php artisan schedule:run && sleep 60`)
- [ ] No Redis — queue and cache use the `database` driver (MySQL `jobs` / `cache` tables)
- [ ] Secrets injected via DO Secrets Manager → k8s Secret → env vars

---

## Security Considerations

- [ ] Brevo webhook endpoint protected by HMAC signature verification middleware
- [ ] AI API keys stored encrypted in `ai_settings` table (Laravel `encrypted` cast)
- [ ] All admin routes behind Filament auth + role check
- [ ] Rate limiting on webhook endpoint (`throttle:60,1`)
- [ ] CSP headers; sandboxed iframe for email preview
- [ ] GDPR note: lead data is business-contact info (B2B), no special category data

---

### Phase 8 — Test Coverage

**Goals:** Complete, maintainable test suite that runs in CI on every push. No feature ships without tests.

#### 8.1 Test Infrastructure
- [ ] Pest v3 as the test runner (already installed via `--pest` flag)
- [ ] `RefreshDatabase` trait on all feature tests — isolated per-test SQLite or MySQL test DB
- [ ] Model factories for every model: `LeadFactory`, `TagFactory`, `EmailThreadFactory`, `EmailMessageFactory`, `EmailDraftFactory`, `UserFactory`, `ImportBatchFactory`
- [ ] Shared test helpers: `actingAsAdmin()`, `actingAsAgent()`, `fakeBrevoResponse()`, `fakeAiResponse()`
- [ ] `Http::fake()` and `Mail::fake()` used in all tests that touch external services — no real HTTP calls in CI

#### 8.2 Unit Tests — Services
- [ ] `OpenRouterProvider`: correct prompt format, parses response, throws on API error
- [ ] `GroqProvider`: same contract
- [ ] `GeminiProvider`: same contract
- [ ] `AiProviderFactory`: resolves correct provider from settings, throws on unknown provider
- [ ] `AiProviderInterface::availableModels()`: returns live list when API succeeds; falls back to `config/ai.php` on failure
- [ ] `CsvLeadImporter`: parses valid CSV, rejects missing `title`, handles duplicate detection
- [ ] `JsonLeadImporter`: parses valid JSON array, rejects invalid rating, handles duplicate
- [ ] `LeadImportPipeline`: orchestrates importer + deduplication + batch recording
- [ ] `BrevoMailService`: builds correct API payload, attaches `X-Lead-ID` and `X-Thread-ID` headers
- [ ] `BrevoInboundParser`: extracts thread ID from `To` address, maps fields correctly

#### 8.3 Unit Tests — Models
- [ ] `Lead`: scopes (`webDevProspects`, `highRating`, `noWebsite`), status transitions, activity auto-logging
- [ ] `EmailThread`: message ordering, `latestMessage()` helper
- [ ] `EmailDraft`: version history, `currentVersion()`, restore version
- [ ] `User`: `emailSettings()` relation, role checks

#### 8.4 Feature Tests — Import
- [ ] Upload valid CSV → job dispatched → leads created with correct fields
- [ ] Upload valid JSON → same
- [ ] Upload CSV with duplicate leads → skip/update behaviour respected
- [ ] Upload invalid file type → validation error returned
- [ ] Import batch record created with correct counts
- [ ] Undo batch → leads soft-deleted

#### 8.5 Feature Tests — Webhooks
- [ ] `POST /webhooks/brevo/inbound` with valid signature → `EmailMessage` created, lead status updated, event fired
- [ ] Invalid signature → 403
- [ ] Unknown thread ID → 422
- [ ] `POST /webhooks/brevo/events` hard_bounce → lead status set to `disqualified`
- [ ] `POST /webhooks/brevo/events` delivered → activity log entry created

#### 8.6 Feature Tests — Email Sending
- [ ] `GenerateColdEmailJob`: calls AI provider (faked), creates `EmailDraft` + `EmailThread` + `EmailMessage`
- [ ] `RefineDraftJob`: calls AI with instruction, saves new draft version
- [ ] `SendEmailJob`: calls Brevo API (faked), records `message_id`, updates draft status to `sent`
- [ ] `SendEmailJob` failure → retries; after max retries → draft marked `failed`

#### 8.7 Feature Tests — Auth & Authorisation
- [ ] Admin can access all settings pages; Agent cannot
- [ ] Agent can manage leads and send emails; cannot access AI settings or user management
- [ ] Unauthenticated request to any admin route → redirected to login

#### 8.8 Livewire Component Tests
- [ ] Conversation view: renders messages in correct order, reply composer toggles modes
- [ ] Draft editor: subject/body update syncs to preview, version history dropdown works
- [ ] Import progress: polls `import_batches` and updates counts live
- [ ] Lead filters: applying "Web Dev Prospects" preset populates correct filter values

#### 8.9 CI Integration
- [ ] `ci.yml` runs: `vendor/bin/pest --parallel` + `vendor/bin/pint --test` + `vendor/bin/phpstan analyse --level=8`
- [ ] Coverage report generated (`--coverage --min=80`) — build fails below 80 %
- [ ] Test results published as GitHub Actions summary (Pest JUnit reporter)

---

## Phase Delivery Order

| # | Phase | Depends on |
|---|---|---|
| 1 | Scaffold + Infra + Seeds + i18n + PWA | — |
| 2 | Lead Management Core | 1 |
| 3 | CSV/JSON Import | 2 |
| 4 | AI Email Generation | 2, 3 |
| 5 | Draft Editor & Preview | 4 |
| 6 | Email Sending & Conversations | 4, 5 |
| 7 | Dashboard & Notifications | 2, 6 |
| 8 | Test Coverage | all phases |
