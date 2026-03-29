# AllLeads CRM

<p align="center">
  <img src="logo.png" alt="AllLeads logo" width="96">
</p>

<p align="center">
  A single-tenant CRM for web-development agencies. Import local business leads, surface high-potential prospects, generate personalised cold emails with free AI models, and track every conversation through to close.
</p>

---

## Features

**Lead Management**
- Import leads from CSV or JSON (Google Maps exports and similar)
- Smart filtering with a built-in **"Web Dev Prospects"** view — rating > 4.5, no website
- Tags, status lifecycle, assignees, notes, call logs, and a full activity timeline

**AI Email Generation**
- Supports **OpenRouter**, **Groq**, and **Google Gemini** free-tier models
- Rich generation settings: language, tone, length, personalisation level, opener style
- Per-campaign and per-user style overrides

**Draft Editor**
- Split-pane editor with live HTML preview
- Refine any draft by chatting with the AI ("make it shorter", "translate to Bulgarian")
- Full version history — restore any previous version

**Email Conversations**
- Send via **Brevo** transactional API
- Inbound replies captured automatically via Brevo webhook
- Gmail-style threaded conversation view per lead
- Manual reply logging as fallback

**Dashboard & Notifications**
- Stats widgets, import timeline chart, email funnel chart
- In-app notification bell for replies, import completions, and failed drafts

**Developer Experience**
- PWA — installable, offline shell, iOS meta tags
- Fully responsive — mobile-first Tailwind, card layouts on small screens
- All UI strings internationalised (`__()` — no hardcoded text in views)
- Laravel Boost with GitHub Copilot & Claude Code (MCP + guidelines + skills)

---

## Stack

| | |
|---|---|
| Framework | Laravel 13 |
| Admin UI | Filament 5 |
| Reactive UI | Livewire 4 |
| Styling | Tailwind CSS 4 |
| Database | MySQL 8 |
| Queue / Cache | Database driver (no Redis) |
| AI | OpenRouter · Groq · Google Gemini |
| Email | Brevo (send + inbound webhook) |
| Auth / Roles | Filament auth · Spatie Permission (Admin, Agent) |
| Containers | Docker + Docker Compose |
| CI/CD | GitHub Actions → GHCR → DigitalOcean K8s |

---

## Quick Start

### Prerequisites

- Docker Desktop 26+
- GNU Make

### Run locally

```bash
git clone https://github.com/YOUR_ORG/allleads.git
cd allleads

cp .env.example .env
make up
make init        # key:generate + migrate + seed
```

Open **http://localhost:8080/admin** and log in with the seeded admin account.

> Default credentials are set via `ADMIN_EMAIL` / `ADMIN_PASSWORD` in your `.env`.  
> Outbound emails in local dev are written to `storage/logs/laravel.log`.

### Useful commands

```bash
make up            # start all containers
make fresh         # drop + re-migrate + re-seed
make test          # run Pest test suite
make lint          # run Laravel Pint
make analyse       # run Larastan (level 8)
make shell         # bash shell inside the app container
make tinker        # Laravel Tinker
```

See [`DEPLOY.md`](DEPLOY.md) for the full local reference and all `make` targets.

---

## AI Setup

API keys for AI providers are configured in **Settings → AI & Email** inside the admin panel. All three providers support free-tier usage — no billing required to get started.

| Provider | Get a free key |
|---|---|
| OpenRouter | https://openrouter.ai/keys |
| Groq | https://console.groq.com/keys |
| Google Gemini | https://aistudio.google.com/apikey |

Add your key to `.env` for local use:

```dotenv
OPENROUTER_API_KEY=
GROQ_API_KEY=
GEMINI_API_KEY=
```

Model lists are fetched live when available and fall back to the bundled list in `config/ai.php`.

---

## Email Setup (Brevo)

See [`EMAILS.md`](EMAILS.md) for full Brevo configuration including:
- Domain verification (DKIM, SPF, DMARC)
- API key and SMTP credentials
- Inbound reply webhook (MX record + webhook URL)
- Per-user sender address verification

---

## Deployment

See [`DEPLOY.md`](DEPLOY.md) for full instructions covering:
- DigitalOcean K8s cluster creation (`doctl`)
- DO Managed MySQL setup
- GHCR image registry and pull secrets
- `ingress-nginx` + `cert-manager` (Let's Encrypt TLS)
- Namespace secrets, first manual deploy
- GitHub Actions CI/CD — staging on `main`, production on release tag

---

## Project Roles

| Role | Can do |
|---|---|
| **Admin** | Everything — AI settings, user management, all leads |
| **Agent** | Manage leads, generate and send emails, view conversations |

---

## Contributing

- All UI strings must go through `__()` — no raw text in views, Filament resources, or Livewire components
- Run `php artisan translations:missing` before every PR
- Tests are required for every feature — CI enforces **≥ 80 % coverage**
- Run `php artisan boost:install` after cloning to set up AI guidelines for your editor (GitHub Copilot or Claude Code)

---

## Docs

| File | Contents |
|---|---|
| [`PLAN.md`](PLAN.md) | Full implementation roadmap with phased checklist |
| [`DEPLOY.md`](DEPLOY.md) | Local setup and DigitalOcean K8s deployment guide |
| [`EMAILS.md`](EMAILS.md) | Brevo email configuration and inbound webhook setup |
