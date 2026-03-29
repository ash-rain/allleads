# AllLeads CRM — Implementation Roadmap

## Overview

A single-tenant CRM for a web-development agency. Imports local business leads from CSV/JSON, surfaces high-potential prospects (rating > 4.5, no website), generates AI cold emails via free cloud LLMs, and tracks email conversations with each lead. Emails sent and received via Brevo.

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| AI Dev Tool | Laravel Boost (`laravel/boost --dev`) — MCP server + AI guidelines + skills for GitHub Copilot & Claude Code |
| Forms / Validation | Laravel Precognition (`laravel/precognition`) — real-time form validation |
| Admin UI | Filament 5 |
| Reactive UI | Livewire 4 |
| Styling | Tailwind CSS 4 |
| Internationalisation | Laravel `lang/` + `__()` helper, topic-grouped PHP files |
| PWA | Web App Manifest + Service Worker |
| Database | MySQL 8 |
| Queue / Cache | Laravel database driver (MySQL `jobs` / `cache` / `sessions` tables) |
| AI | OpenRouter · Groq · Google Gemini (free tiers) |
| Email | Brevo — transactional sending + inbound webhook |
| Auth | Filament auth (built-in) |
| Roles | Spatie Laravel Permission — Admin, Agent |
| Containers | Docker + Docker Compose |
| CI/CD | GitHub Actions → GHCR → DigitalOcean K8s |

---

## Domain Model

```
users                — Admin / Agent accounts
leads                — imported business contacts
lead_tags (pivot)    — many-to-many with tags
tags                 — reusable labels
lead_notes           — notes and call logs per lead
lead_activities      — immutable timeline events per lead
import_batches       — tracks each import run
email_campaigns      — batch email-generation jobs
email_drafts         — per-lead AI-generated draft (versioned)
email_draft_versions — draft edit history
email_threads        — conversation container per lead
email_messages       — individual messages (outbound + inbound)
ai_settings          — global AI provider/model/style config
user_email_settings  — per-user sender, CC, signature, header
user_filter_presets  — saved filter configurations per user
```

### Lead fields
| Field | Type | Notes |
|---|---|---|
| `title` | string | business name |
| `category` | string | business type |
| `address` | string | full address |
| `phone` | string | nullable |
| `website` | string | nullable — absence is the key qualifier |
| `email` | string | nullable |
| `review_rating` | decimal | 0–5 |
| `status` | enum | `new \| contacted \| replied \| closed \| disqualified` |
| `assignee_id` | FK | → users |
| `import_batch_id` | FK | → import_batches |
| `source` | enum | `csv \| json \| manual` |

---

## Phases

---

### Phase 1 — Scaffold & Infrastructure

**Goals:** Runnable local dev environment, CI pipeline, K8s manifests, seeds, i18n setup, PWA shell.

#### 1.1 Laravel Bootstrap
- [ ] `laravel new allleads --pest`
- [ ] Install runtime packages:
  - `filament/filament` (v5)
  - `livewire/livewire` (v4)
  - `laravel/precognition` — real-time form validation without full page requests
  - `spatie/laravel-permission`
  - `league/csv`
- [ ] Install dev packages:
  - `laravel/boost` — AI development tool; MCP server + ecosystem guidelines + skills for AI coding agents
- [ ] Run `php artisan boost:install` — when prompted for agents select **GitHub Copilot** and **Claude Code**; when prompted for features select **guidelines**, **skills**, and **MCP**
- [ ] When asked which skills to install, select all applicable:
  - `livewire-development` (auto-detected via `livewire/livewire`)
  - `pest-testing` (auto-detected via `pestphp/pest`)
  - `tailwindcss-development` (auto-detected via Tailwind CSS)
- [ ] **GitHub Copilot (VS Code):** open command palette → `MCP: List Servers` → select `laravel-boost` → Start server
- [ ] **Claude Code:** enabled automatically; if not, run `claude mcp add -s local -t stdio laravel-boost php artisan boost:mcp`
- [ ] Add to `composer.json` post-update-cmd: `@php artisan boost:update --ansi` — keeps guidelines and skills current
- [ ] Add Boost-generated files to `.gitignore` (each developer regenerates with `boost:install`):
  ```
  .mcp.json
  CLAUDE.md
  AGENTS.md
  boost.json
  .ai/
  ```
- [ ] Add project-specific AI guidelines in `.ai/guidelines/` — document conventions: lead statuses, thread ID format, Brevo header names, lang key naming
- [ ] Run `php artisan filament:install --panels`
- [ ] Configure Tailwind 4 with the Filament preset (`@tailwindcss/vite`, Filament colour palette)
- [ ] Precognition usage: import/campaign/draft modals use `useForm()` for inline field-level validation before hitting the server

#### 1.2 Docker Compose
```
services:
  app        — PHP 8.4-FPM
  nginx      — reverse proxy → localhost:8080
  mysql      — MySQL 8 (same engine as DO Managed MySQL in production)
  queue      — php artisan queue:work --sleep=3 --tries=3
  scheduler  — loop: php artisan schedule:run && sleep 60
```
- [ ] `docker/php/Dockerfile` — PHP 8.4-FPM, all required extensions, Composer
- [ ] `docker/nginx/default.conf`
- [ ] `.env.example` — all keys documented with inline comments
- [ ] `Makefile` targets: `up`, `down`, `init`, `migrate`, `seed`, `fresh`, `test`, `lint`, `analyse`, `shell`, `tinker`, `artisan`, `composer`, `npm`
- [ ] Local mail: `MAIL_MAILER=log` — emails written to `storage/logs/laravel.log`

#### 1.3 GitHub Actions CI/CD
- [ ] `ci.yml` — triggers on every push/PR:
  - `vendor/bin/pint --test` (lint)
  - `vendor/bin/pest --parallel` (tests)
  - `vendor/bin/phpstan analyse --level=8` (static analysis)
  - Coverage `--min=80`; JUnit summary posted to Actions
- [ ] `deploy.yml` — triggers on push to `main` (staging) and release tag `v*.*.*` (production):
  - Build Docker image → tag with git SHA → push to GHCR
  - `kustomize edit set image` → patch overlay → `kubectl apply`
  - `kubectl rollout status --timeout=5m`
  - Post-deploy: `php artisan migrate --force` in init container
- [ ] Required secrets: `GHCR_TOKEN`, `DO_KUBECONFIG`, `APP_KEY`, `DB_PASSWORD`, `BREVO_API_KEY`, `BREVO_WEBHOOK_SECRET`, `OPENROUTER_API_KEY`, `GROQ_API_KEY`, `GEMINI_API_KEY`

#### 1.4 K8s Manifests (`k8s/`)
```
k8s/
  cluster-issuer.yaml              — Let's Encrypt ClusterIssuer (applied once)
  base/
    deployment-app.yaml            — PHP-FPM app, readiness probe GET /up
    deployment-queue.yaml          — queue worker, restarts on failure
    deployment-scheduler.yaml      — schedule:run loop, single replica
    service.yaml
    ingress.yaml                   — cert-manager TLS annotations
    configmap.yaml                 — non-secret env vars
    hpa.yaml                       — HPA on app deployment only
  overlays/
    staging/                       — namespace: allleads-staging, replicas: 1
    production/                    — namespace: allleads-production, replicas: 2
```
- [ ] Rolling update: `maxSurge: 1`, `maxUnavailable: 0` — zero downtime
- [ ] Staging auto-deploys on `main`; production requires GitHub Environment approval gate

#### 1.5 Seeds & Bootstrap Data
- [ ] `AdminSeeder` — email/password from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`)
- [ ] `AgentSeeder` — one demo agent
- [ ] `TagSeeder` — `hot-lead`, `no-website`, `high-rating`, `web-dev-prospect`, `called`, `emailed`
- [ ] `DemoLeadSeeder` — 20 leads via Faker (Bulgarian business names, realistic data matching CSV/JSON schema)
- [ ] `AiSettingsSeeder` — provider: OpenRouter, model: `mistralai/mistral-7b-instruct:free`, language: English, tone: Professional

#### 1.6 Internationalisation (i18n)
> **Rule: no hardcoded text in views, Filament resources, Livewire components, or validation messages. All strings through `__()`.**

- [ ] `lang/en/` topic-grouped files: `common.php`, `auth.php`, `leads.php`, `emails.php`, `ai.php`, `notifications.php`
- [ ] `lang/en/filament/` — Filament panel label overrides (column headers, action labels, form field labels)
- [ ] All Filament definitions use `->label(__('leads.field_name'))` — never a raw string literal
- [ ] Livewire validation messages, flash notifications, and Blade strings all use lang keys
- [ ] Custom Artisan command `translations:missing` — diffs `lang/en/` against extracted view strings, reports any gaps
- [ ] Convention documented in `CONTRIBUTING.md`: _"Run `php artisan translations:missing` before every PR."_

#### 1.7 PWA & Responsive Design

**PWA**
- [ ] `public/manifest.json` — `display: standalone`, theme colour, icons 192×192 / 512×512 / maskable
- [ ] `public/sw.js` — cache app shell (CSS, JS, fonts) on install; network-first for data; offline fallback
- [ ] Register service worker in `resources/js/app.js`
- [ ] Base Blade layout: `<link rel="manifest">`, `<meta name="theme-color">`, iOS `apple-mobile-web-app-*` meta tags
- [ ] `resources/views/offline.blade.php` — branded offline page

**Responsive Design**
- [ ] Mobile-first Tailwind throughout — base = mobile, `md:` / `lg:` for wider screens
- [ ] Filament sidebar: collapsible on mobile (built-in)
- [ ] Leads table: card layout on mobile (`sm:hidden` table → `sm:block` card grid)
- [ ] Conversation view: stacked on mobile, split-pane on `lg:`
- [ ] Draft editor: stacked on mobile, side-by-side on `lg:`
- [ ] Dashboard: 1-col → 2-col `md:` → 4-col `xl:`
- [ ] Mobile bottom tab bar for primary nav (Leads, Campaigns, Conversations)

---

### Phase 2 — Lead Management Core

**Goals:** Full CRUD, smart filtering, tags, assignees, notes, activity timeline.

#### 2.1 Leads Resource (Filament)
- [ ] `LeadResource` table columns: name, category, rating (badge colour by value), website (✓/✗ icon), email, status (badge), assignee avatar, tags, created_at
- [ ] Sortable + searchable columns
- [ ] Bulk actions: assign user, change status, add/remove tag, delete
- [ ] Lead detail page — tabbed: **Overview** | **Conversation** | **Notes & Calls** | **Activity**

#### 2.2 Smart Filters & Saved Presets
- [ ] Filter sidebar: rating range, has/no website, has/no email, category (multiselect), status, tags, assignee, import batch, date range
- [ ] **"Web Dev Prospects"** — built-in named preset: `rating > 4.5 AND website IS NULL`, pinned as a top-nav shortcut
- [ ] `user_filter_presets` table — users can save/name/delete their own filter combinations

#### 2.3 Tags & Status
- [ ] Tags: inline multi-select on table row and detail page
- [ ] Status: enforced flow `new → contacted → replied → closed`; `disqualified` reachable from any state
- [ ] Status and tag changes auto-logged to activity timeline

#### 2.4 Notes & Call Logs
- [ ] `LeadNote` — type `note | call`, rich-text body, `created_by`, timestamp
- [ ] Call log fields: duration (minutes), outcome (dropdown: `interested | not interested | no answer | callback`)
- [ ] Timeline component on Notes & Calls tab

#### 2.5 Activity Timeline
- [ ] Immutable `LeadActivity` events auto-recorded on: import, status change, tag add/remove, assignee change, email sent, reply received, note added
- [ ] Vertical timeline on Activity tab (Filament Infolists)

---

### Phase 3 — CSV & JSON Import

**Goals:** Validated, duplicate-safe bulk import with queued processing and progress feedback.

#### 3.1 Import UI
- [ ] Filament table action "Import Leads" → modal with file upload (CSV or JSON, MIME auto-detected)
- [ ] Options in modal: assign imported leads to a user, apply a tag to all imported leads
- [ ] Uses Precognition for live file-type validation before submit

#### 3.2 Import Pipeline
- [ ] `ImportLeadsJob` (queued) — `league/csv` for CSV, `json_decode` for JSON
- [ ] Row validation: `title` required, `review_rating` numeric 0–5, `website` valid URL if present
- [ ] Duplicate detection: exact match on `phone`, or `title + address` similarity — offer **skip / update / create**
- [ ] Each run creates an `ImportBatch` record (UUID, filename, status, counts)
- [ ] Progress tracked via `import_batches.progress` column + Livewire polling

#### 3.3 Import Batches Page
- [ ] Table: filename, imported at, total / created / updated / skipped / failed counts
- [ ] "Undo" action — soft-deletes all leads in batch, marks batch as `undone`

---

### Phase 4 — AI Email Generation

**Goals:** Per-lead AI-drafted cold emails via configurable providers; rich style settings.

#### 4.1 AI Settings Page (Admin — Settings → AI & Email)

**Provider tabs** (one per provider):
- [ ] **OpenRouter** — API key, model picker (fetched live from `/models` filtered to `:free`; falls back to `config/ai.php`), temperature, max tokens
- [ ] **Groq** — API key, model picker (live from Groq `/models`; falls back to `config/ai.php`), temperature
- [ ] **Gemini** — API key, model picker (static from `config/ai.php` — no public model-list endpoint), temperature
- [ ] Active provider selector with provider logo

**Generation style settings:**
- [ ] Language (searchable select: English, Bulgarian, German, Spanish, French + custom)
- [ ] Tone: Professional · Friendly · Casual · Persuasive · Consultative
- [ ] Length: Short (3–4 sentences) · Medium (2 paragraphs) · Long (detailed pitch)
- [ ] Personalisation: Low (generic) · Medium (uses category) · High (uses category + address + rating context)
- [ ] Opener style: Question · Statement · Compliment · Statistic
- [ ] Include toggles: Portfolio mention, Free audit offer, CTA button, PS line
- [ ] Advanced toggle → custom system prompt textarea
- [ ] "Preview prompt" button — renders the final prompt that will be sent to the AI
- [ ] Per-user overrides: agents override language + tone from their profile

#### 4.2 `config/ai.php`
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
        'models'   => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'mixtral-8x7b-32768', 'gemma2-9b-it'],
    ],
    'gemini' => [
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
        'models'   => ['gemini-2.0-flash-lite', 'gemini-1.5-flash-8b', 'gemini-1.5-flash'],
    ],
];
```
- [ ] No model IDs hardcoded anywhere outside this file
- [ ] `availableModels()` on each provider: live fetch → cache 1 hour → fallback to config on error

#### 4.3 AI Provider Abstraction
```php
interface AiProviderInterface {
    public function complete(string $systemPrompt, string $userPrompt, array $options): string;
    public function availableModels(): array;
}
// OpenRouterProvider | GroqProvider | GeminiProvider
// Resolved by AiProviderFactory::make(AiSetting $setting)
```

#### 4.4 Campaign & Job
- [ ] "Generate Cold Emails" bulk action on Leads table → modal: campaign name, provider/model override, style overrides, estimated cost ("$0 — free tier")
- [ ] Creates `EmailCampaign` → dispatches one `GenerateColdEmailJob` per lead
- [ ] `GenerateColdEmailJob`: builds prompt from lead data + settings → calls provider → creates `EmailDraft` (status: `draft`) + `EmailThread` + `EmailMessage` (role: `ai_draft`)
- [ ] On failure: retry ×3, then mark draft `failed` with error stored

---

### Phase 5 — Draft Editor & Preview

**Goals:** Edit, refine with AI, preview, and schedule drafts before sending.

#### 5.1 Draft Editor (Livewire)
- [ ] Accessible from lead detail → Conversation tab → Draft card
- [ ] Split-pane (side-by-side on `lg:`, stacked on mobile): **left = editor**, **right = sandboxed iframe preview**
- [ ] Fields: Subject, Body (Trix rich text), CC override
- [ ] Auto-fills sender name, signature, header image from user email settings

#### 5.2 Refine with AI
- [ ] Chat thread below editor (Livewire, streamed responses)
- [ ] User types instruction → `RefineDraftJob` → AI rewrites body → appears in editor
- [ ] Version history stored in `email_draft_versions`; version picker dropdown to restore any version

#### 5.3 Draft Actions
- [ ] **Send Now** → status `queued_for_send` → dispatches `SendEmailJob`
- [ ] **Schedule** → datetime picker → sets `send_at`, job dispatched with delay
- [ ] **Discard** → soft-delete draft, thread preserved

---

### Phase 6 — Email Sending & Conversations

**Goals:** Send via Brevo; receive replies; full threaded conversation UI.

#### 6.1 Sending
- [ ] `SendEmailJob` — calls Brevo Transactional API (`Illuminate\Http\Client`), stores returned `message_id`
- [ ] Custom headers on every outbound email: `X-Lead-ID`, `X-Thread-ID`
- [ ] `Reply-To: replies+{thread_id}@inbound.yourdomain.com` for webhook reply matching
- [ ] Sender address pulled from user's email settings; Brevo domain-verified reminder shown if unverified

#### 6.2 Inbound Webhook
- [ ] `POST /webhooks/brevo/inbound` — HMAC signature verified via middleware
- [ ] Parse `To` address → extract `thread_id` → match `EmailThread`
- [ ] Create `EmailMessage` (role: `lead_reply`) → fire `LeadRepliedEvent`
- [ ] `LeadRepliedEvent` listener: set lead status `replied`, log activity, notify assignee

#### 6.3 Bounce & Event Webhook
- [ ] `POST /webhooks/brevo/events` — handles `hard_bounce`, `spam`, `unsubscribed` → set lead `disqualified`, log activity
- [ ] Handles `delivered` → log activity entry

#### 6.4 Manual Reply (fallback)
- [ ] "Log Manual Reply" button on conversation view → textarea + date picker → creates `EmailMessage` (source: `manual`)

#### 6.5 Conversation View (Livewire)
- [ ] Gmail-style thread on lead detail → Conversation tab
- [ ] Outbound messages right-aligned (brand colour), inbound left-aligned (neutral)
- [ ] Expand/collapse per message; sender, timestamp, rendered HTML body
- [ ] Reply Composer at bottom: toggle "Write myself" vs "Ask AI to draft"
- [ ] AI draft → opens Draft Editor modal for review before sending

#### 6.6 User Email Settings
- [ ] Filament profile page: Sender Name, Sender Email, Reply-To, default CC, default BCC
- [ ] Header image upload (`storage/app/email-headers`, served via signed URL)
- [ ] Signature: Trix editor with variables `{{user_name}}`, `{{user_email}}`, `{{company_name}}`
- [ ] Live rendered preview of assembled email (signature + header)

---

### Phase 7 — Dashboard & Notifications

#### 7.1 In-App Notifications
- [ ] Filament notification bell — events: reply received, draft generation failed, import complete
- [ ] Database-backed notifications (no Redis/Reverb needed) — bell polls on a 30-second interval via Livewire

#### 7.2 Dashboard
- [ ] Stats widgets: Total Leads, Web Dev Prospects, Emails Sent Today, Open Threads, Replies Received
- [ ] Chart: leads imported over time (grouped by batch date)
- [ ] Chart: email status funnel (draft → sent → replied → closed)
- [ ] Recent activity feed (last 20 `lead_activities` across all leads)
- [ ] "Web Dev Prospects" quick-access card linking to the saved filter

---

### Phase 8 — Test Coverage

**Goals:** Full test suite in CI, no feature ships without tests.

#### 8.1 Setup
- [ ] Pest v3 (installed via `--pest`)
- [ ] `RefreshDatabase` on all feature tests; SQLite in-memory for speed in CI
- [ ] Factories: `LeadFactory`, `TagFactory`, `EmailThreadFactory`, `EmailMessageFactory`, `EmailDraftFactory`, `UserFactory`, `ImportBatchFactory`
- [ ] Helpers: `actingAsAdmin()`, `actingAsAgent()`, `fakeAiResponse()`, `fakeBrevoResponse()`
- [ ] `Http::fake()` and `Mail::fake()` everywhere external services are touched

#### 8.2 Unit — Services
- [ ] Each AI provider: correct prompt, parses response, throws on API error
- [ ] `AiProviderFactory`: resolves correct class; throws on unknown provider
- [ ] `availableModels()`: returns live list; falls back to `config/ai.php` on failure
- [ ] `CsvLeadImporter` + `JsonLeadImporter`: valid parse, missing fields, duplicate detection
- [ ] `BrevoMailService`: correct payload, `X-Lead-ID`/`X-Thread-ID` headers
- [ ] `BrevoInboundParser`: extracts thread ID from `To` address

#### 8.3 Unit — Models
- [ ] `Lead` scopes: `webDevProspects()`, `highRating()`, `noWebsite()`
- [ ] Status transition logic and activity auto-log
- [ ] `EmailDraft` version history and restore
- [ ] `EmailThread` message ordering

#### 8.4 Feature — Import
- [ ] Valid CSV → job dispatched → correct leads created
- [ ] Valid JSON → same
- [ ] Duplicate → skip/update behaviour
- [ ] Invalid file type → validation error
- [ ] Undo batch → leads soft-deleted

#### 8.5 Feature — Webhooks
- [ ] Brevo inbound: valid sig → message created, status updated; invalid sig → 403; unknown thread → 422
- [ ] Brevo events: `hard_bounce` → disqualified; `delivered` → activity logged

#### 8.6 Feature — Jobs & Email
- [ ] `GenerateColdEmailJob` (faked AI) creates draft + thread + message
- [ ] `RefineDraftJob` saves new version
- [ ] `SendEmailJob` (faked Brevo) records `message_id`, marks draft `sent`
- [ ] Retry exhaustion → draft marked `failed`

#### 8.7 Feature — Auth
- [ ] Admin accesses all settings; Agent cannot access AI settings or user management
- [ ] Unauthenticated → redirect to login

#### 8.8 Livewire Components
- [ ] Conversation view: message order, reply toggle
- [ ] Draft editor: preview sync, version restore
- [ ] Import modal: Precognition inline validation, progress polling
- [ ] Lead filters: "Web Dev Prospects" preset applies correct values

#### 8.9 CI
- [ ] `ci.yml`: `pest --parallel --coverage --min=80` + `pint --test` + `phpstan --level=8`
- [ ] JUnit reporter → GitHub Actions summary
- [ ] Build fails if coverage drops below 80 %

---

## File / Directory Structure

```
app/
  Filament/
    Resources/
      LeadResource/           — CRUD, filters, bulk actions
      EmailCampaignResource/
    Pages/Dashboard.php
    Widgets/                  — stats + chart widgets
  Http/Controllers/Webhooks/
    BrevoInboundController.php
    BrevoEventsController.php
  Jobs/
    ImportLeadsJob.php
    GenerateColdEmailJob.php
    RefineDraftJob.php
    SendEmailJob.php
  Livewire/
    ConversationView.php
    DraftEditor.php
    ImportProgress.php
  Services/
    Ai/
      AiProviderInterface.php
      AiProviderFactory.php
      OpenRouterProvider.php
      GroqProvider.php
      GeminiProvider.php
    Import/
      CsvLeadImporter.php
      JsonLeadImporter.php
      LeadImportPipeline.php
    Brevo/
      BrevoMailService.php
      BrevoInboundParser.php
  Models/                     — one file per model (see Domain Model)
  Events/
    LeadRepliedEvent.php
    LeadImportedEvent.php
  Listeners/
  Policies/                   — LeadPolicy, EmailDraftPolicy
config/
  ai.php                      — provider endpoints + fallback model lists
database/
  migrations/
  seeders/
lang/
  en/
    auth.php  common.php  leads.php  emails.php  ai.php  notifications.php
  en/filament/               — Filament panel label overrides
public/
  manifest.json              — PWA
  sw.js                      — service worker
  icons/                     — 192px, 512px, maskable
resources/views/
  offline.blade.php          — PWA offline fallback
docker/
  nginx/default.conf
  php/Dockerfile
  php/php.ini
k8s/
  cluster-issuer.yaml
  base/
    deployment-app.yaml
    deployment-queue.yaml
    deployment-scheduler.yaml
    service.yaml  ingress.yaml  configmap.yaml  hpa.yaml
  overlays/
    staging/kustomization.yaml
    production/kustomization.yaml
tests/
  Unit/Services/
  Unit/Models/
  Feature/Import/
  Feature/Webhooks/
  Feature/Jobs/
  Livewire/
.github/workflows/
  ci.yml
  deploy.yml
```

---

## Security

- [ ] Brevo webhook endpoints: HMAC signature middleware, rate-limited (`throttle:60,1`)
- [ ] AI API keys: `encrypted` cast on `ai_settings` model
- [ ] All routes behind Filament auth + Spatie role checks
- [ ] CSP headers; sandboxed iframe for email preview
- [ ] GDPR: B2B contact data only — no special-category personal data

---

## Phase Delivery Order

| # | Phase | Key output | Depends on |
|---|---|---|---|
| 1 | Scaffold + Infra | Running app, Docker, CI, K8s, seeds, i18n, PWA | — |
| 2 | Lead Core | Full lead CRUD, filters, timeline | 1 |
| 3 | Import | CSV/JSON import pipeline | 2 |
| 4 | AI Generation | AI settings, campaigns, draft creation | 2, 3 |
| 5 | Draft Editor | Editor, AI refine, version history | 4 |
| 6 | Email & Conversations | Brevo send/receive, threaded view | 4, 5 |
| 7 | Dashboard | Stats, charts, notifications | 2, 6 |
| 8 | Tests | Full test suite, CI coverage gate | all |
