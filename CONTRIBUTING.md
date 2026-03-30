# Contributing to AllLeads CRM

Thank you for your interest in contributing! Please read these guidelines before opening a PR.

---

## Development Setup

```bash
# Prerequisites: PHP 8.4, Composer 2, Node 20+, Docker + Docker Compose
git clone <repo-url> allleads && cd allleads
make init        # starts Docker, installs deps, runs migrations + seeds
```

Open `http://localhost:8080/` and log in with the credentials from `.env`.

---

## Code Standards

| Tool | Command | Notes |
|---|---|---|
| **Laravel Pint** | `make lint` | PSR-12 + opinionated rules; runs in CI |
| **Larastan** | `make analyse` | Level 8; must pass before merge |
| **Pest** | `make test` | All tests must be green |

---

## Language / Translations

All user-visible strings **must** go through Laravel's translation functions:

```php
// Blade
{{ __('leads.status_new') }}
@lang('emails.draft_action_approve')

// PHP
__('ai.saved')
trans('notifications.import_completed_title')
```

### Adding New Keys

1. Add the key → string pair to the appropriate file under `lang/en/`.
2. Run the `translations:missing` command to verify no keys are missing:

   ```bash
   php artisan translations:missing
   # or with Docker:
   make artisan CMD="translations:missing"
   ```

3. The CI pipeline runs `translations:missing --fail`, which will block the PR
   if any keys are used in code but not defined in `lang/en/`.

### Translation File Conventions

| File | Purpose |
|---|---|
| `lang/en/common.php` | App-wide labels (CRUD actions, nav groups, timestamps) |
| `lang/en/leads.php` | Lead resource — fields, statuses, filters, actions |
| `lang/en/emails.php` | Campaign / draft / thread / message labels |
| `lang/en/ai.php` | AI settings page labels |
| `lang/en/notifications.php` | Notification titles and body templates |
| `lang/en/auth.php` | Authentication messages |

---

## Branch Strategy

- `main` — protected; auto-deploys to production on merge
- `develop` — integration branch; auto-deploys to staging
- Feature branches: `feat/<short-description>`
- Bug branches: `fix/<short-description>`

Merge via Pull Request. At least one approval required to merge to `main`.

---

## Commit Convention

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(leads): add bulk status-change action
fix(import): handle BOM in UTF-8 CSV files
docs(readme): update docker setup instructions
```

---

## Writing Tests

Tests live in `tests/`. We use **Pest PHP** for all test types.

```bash
# Run all tests
make test

# Run a specific test file
./vendor/bin/pest tests/Feature/LeadImportTest.php

# Run with coverage
./vendor/bin/pest --coverage --min=80
```

- Unit tests go in `tests/Unit/`
- Feature (HTTP) tests go in `tests/Feature/`
- Livewire component tests go in `tests/Livewire/`

---

## Manually Installing `laravel/boost`

`laravel/boost` is an interactive tool and must be installed manually once:

```bash
composer require laravel/boost --dev
php artisan boost:install
# Choose: GitHub Copilot + Claude Code
# Features: guidelines, skills, MCP
# Skills: livewire-development, pest-testing, tailwindcss-development
```

This generates `.ai/` files and `boost.json`, which are `.gitignore`d and should
stay local to each developer's environment.
