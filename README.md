<p align="center">
  <img src="public/img/logo.png" alt="AllLeads CRM logo" width="120">
</p>

# AllLeads CRM

A single-tenant CRM built for web-development agencies. Import local business
leads from CSV or JSON, surface high-potential prospects automatically, generate
personalised AI cold emails through free cloud LLMs, and track every
conversation with each lead — all from one Filament-powered admin panel.

---

## What it does

| Capability | Details |
|---|---|
| **Lead management** | Full CRUD with smart filters, tags, assignees, call logs, and an immutable activity timeline |
| **Web Dev Prospects view** | Built-in filter preset: `rating > 4.5 AND no website` — the hottest targets surfaced instantly |
| **CSV / JSON import** | Drag-and-drop file upload, queued processing, duplicate detection, per-batch undo |
| **AI cold-email generation** | Bulk-generate drafts via OpenRouter, Groq, or Gemini (all free tiers); configurable tone, length, personalisation, opener style |
| **Lead Intelligence Hub** | AI-powered prospect analysis + deep website scraping; extracts business insights, sales angles, pain points; auto-injected into email generation |
| **Draft editor** | Split-pane editor with live HTML preview; refine with AI chat; full version history |
| **Email sending** | Brevo Transactional API; custom `X-Lead-ID` / `X-Thread-ID` headers for reply routing |
| **Inbound conversations** | Brevo inbound webhook; HMAC-verified; Gmail-style threaded view per lead |
| **Event webhooks** | Handles hard bounces, spam, and unsubscribes — auto-disqualifies leads |
| **Dashboard** | Stats widgets, leads-imported chart, email funnel chart, live activity feed |
| **In-app notifications** | Bell icon — reply received, draft failed, import complete, analysis failed (30 s polling, no Reverb needed) |
| **Roles** | Admin (full access) · Agent (own leads + email only) via Spatie Permission |
| **PWA** | Installable on desktop and mobile; offline-capable service worker |
| **i18n** | All strings through `__()` helpers; topic-grouped `lang/en/` files |

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 / PHP 8.4 |
| Admin UI | Filament 5 |
| Reactive components | Livewire 4 |
| Styling | Tailwind CSS 4 |
| Database | MySQL 8 (SQLite for tests) |
| Queue / Cache / Sessions | Laravel `database` driver — no Redis required |
| AI providers | OpenRouter · Groq · Google Gemini (free tiers) |
| Email | Brevo — transactional send + inbound webhook |
| Auth & Roles | Filament auth + Spatie Laravel Permission |
| Containers | Docker + Docker Compose |
| CI/CD | GitHub Actions → GHCR → DigitalOcean Kubernetes |

---

## Quick start

See [DEPLOY.md](DEPLOY.md) for the full setup guide.

**With Docker (recommended):**

```bash
git clone https://github.com/ash-rain/allleads.git && cd allleads
cp .env.example .env
make up      # spin up app, nginx, mysql, queue, scheduler
make init    # key:generate + migrate + seed
```

Open **http://localhost:8080/** — log in with `admin@allleads.dev` / `password`.

**Without Docker** (PHP 8.4 + MySQL locally):

```bash
composer install && npm install && npm run build
cp .env.example .env
php artisan key:generate
# edit .env: set DB_* to your local MySQL and APP_URL=http://localhost:8000
php artisan migrate --seed
php artisan serve
```

---

## Project layout

```
app/
  Filament/Resources/       LeadResource, EmailCampaignResource, ImportBatchResource
  Filament/Pages/           Dashboard, AiSettings
  Filament/Widgets/         Stats + chart + activity widgets
  Http/Controllers/Webhooks/ BrevoInboundController, BrevoEventsController
  Jobs/                     ImportLeadsJob, GenerateColdEmailJob, RefineDraftJob, SendEmailJob
  Livewire/                 ConversationView, DraftEditor, ImportProgress, LeadNotes, LeadActivity
  Services/Ai/              AiProviderInterface + OpenRouter/Groq/Gemini implementations
  Services/Brevo/           BrevoMailService, BrevoInboundParser
  Services/Import/          CsvLeadImporter, JsonLeadImporter, LeadImportPipeline
  Models/                   Lead, EmailDraft, EmailThread, EmailMessage, ImportBatch, ...
config/ai.php               Provider endpoints + fallback model lists
lang/en/                    common · auth · leads · emails · ai · notifications
docker/                     PHP 8.4-FPM Dockerfile + nginx config
k8s/                        Kubernetes manifests (base + staging/production overlays)
.github/workflows/          ci.yml (test + lint + analyse) · deploy.yml (GHCR → K8s)
tests/
  Unit/Models/              Lead, EmailDraft model behaviour
  Unit/Services/            Import pipeline, Brevo parser, AI providers
  Feature/                  Webhooks, Jobs, Import — 8 tests
  Livewire/                 ConversationView, DraftEditor, ImportProgress, LeadNotes — 4 tests
```

---

## Running tests

```bash
php artisan test                     # full suite (23 tests, ~2 s)
php artisan test tests/Unit/         # unit only
php artisan test tests/Feature/      # feature only
php artisan test tests/Livewire/     # Livewire components
```

Tests use SQLite `:memory:` and `Http::fake()` / `Mail::fake()` — no real credentials needed.

---

## AI provider configuration

Go to **Settings → AI & Email** in the admin panel. Choose a provider, paste your
(free-tier) API key, pick a model, and adjust generation style:

- **Language** — English, Bulgarian, and more
- **Tone** — Professional · Friendly · Casual · Persuasive · Consultative
- **Length** — Short · Medium · Long
- **Personalisation** — Low · Medium · High (uses business category, address, and rating context)

Model lists are fetched live from each provider's API and cached for one hour,
with automatic fallback to the defaults in `config/ai.php`.

### Automatic rate-limit recovery

Free-tier models are sometimes rate-limited by the upstream provider. The app
handles this transparently at two levels so jobs never fail from a transient 429:

1. **Model cycling** — when a model returns 429, the provider tries every other
   model in `config/ai.php` for that provider before giving up.
2. **Provider fallback** — if the entire provider is exhausted, the app
   automatically retries with the next configured provider (openrouter → groq →
   gemini), skipping any that have no API key set.

Only 429 rate-limit errors trigger fallback; authentication failures and
malformed responses propagate immediately. A `Log::warning` entry is written
each time a model or provider is skipped.

---

## Deployment

The app ships with a full CI/CD pipeline:

- **Every push / PR** — `pint --test`, `pest`, `phpstan --level=8`
- **Merge to `main`** — builds Docker image → pushes to GHCR → rolls out to the staging K8s namespace
- **GitHub Release tag `v*.*.*`** — same path, but targets the production namespace (manual approval gate)

See [DEPLOY.md](DEPLOY.md) for the complete DigitalOcean Kubernetes setup walkthrough.

---

## License

MIT
