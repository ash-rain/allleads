# AllLeads SaaS Conversion Plan

**Date:** 2026-04-15
**Positioning:** AI prospecting for local service businesses
**Model:** BYOK (Bring Your Own Key) — users supply their own AI API keys

---

## Strategic Decisions

- **Target market:** Local service businesses (web dev agencies, marketing agencies, accountants, insurance brokers, freelancers)
- **AI monetisation:** None — BYOK model. Users bring their own keys. Zero margin pressure.
- **What we monetise:** The orchestration, workflow, data pipeline, prospecting intelligence, and team infrastructure around the AI
- **Key differentiator:** Prospecting playbooks by vertical — pre-built filter + scoring + template bundles

---

## Pricing Tiers

| Feature | Solo ($49/mo) | Team ($99/mo) | Agency ($199/mo) |
|---------|---------------|---------------|------------------|
| Users | 1 | 5 | Unlimited |
| Businesses | 1 | 2 | Unlimited |
| Active Leads | 1,000 | 5,000 | Unlimited |
| CSV/JSON Import | Yes | Yes | Yes |
| AI Email Generation (BYOK) | Yes | Yes | Yes |
| Website Analysis | Yes | Yes | Yes |
| Prospecting Playbooks (pre-built) | No | Yes | Yes |
| Custom Playbooks | No | No | Yes |
| Direct Lead Pulling (Google Places) | No | Yes | Yes |
| Automation Rules | Yes | Yes | Yes |
| API Access | No | No | Yes |
| White-Label Emails | No | No | Yes |
| Support | Community | Priority | Priority + Custom |

---

## Phase 1: Subscription & Billing (Weeks 1-3)

### 1.1 Install Laravel Cashier (Stripe)

- `composer require laravel/cashier`
- Publish migrations: subscriptions, subscription_items
- Add Stripe fields to `businesses` table: `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`

### 1.2 Subscription Plan Model

Create `SubscriptionPlan` model with feature matrix:

```
subscription_plans:
  id, name, stripe_price_id, price_monthly, max_users, max_businesses,
  max_active_leads, has_playbooks, has_custom_playbooks, has_api_access,
  has_white_label, has_direct_lead_pulling, created_at, updated_at
```

Seed with Solo, Team, Agency plans.

### 1.3 Business Model Enhancements

Add to `Business` model:
- `use Billable` trait (Cashier)
- `currentPlan()` — returns SubscriptionPlan (or free trial)
- `canUseFeature(string $feature)` — checks plan matrix
- `canAddUser()` — checks user count vs limit
- `canAddBusiness()` — checks business count vs limit
- `countActiveLeads()` — count non-archived leads
- `subscriptionStatus()` — trial | active | past_due | canceled | inactive

### 1.4 Environment Config

```env
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

---

## Phase 2: Tier Enforcement (Weeks 2-4)

### 2.1 Feature Gates & Middleware

- `VerifySubscriptionLimits` middleware
- `FeatureGateProvider` with gates:
  - `can-add-user`, `can-add-business`
  - `can-use-playbooks`, `can-use-custom-playbooks`
  - `can-access-api`, `can-use-white-label`
  - `can-pull-leads`

### 2.2 Update Existing Resources

- `LeadResource` — disable creation if lead limit reached
- `LeadPolicy` — gate check on `create()`
- `ImportLeadsJob` — check limit before processing
- Show upgrade prompts when limits hit

### 2.3 Usage Tracking

- `UsageEvent` model for audit trail
- Track: leads imported, users added, businesses created
- Denormalize counts to `businesses` table for fast lookups

---

## Phase 3: BYOK Validation (Weeks 2-3, parallel)

**Current state:** Already per-business encrypted key storage via `AiSetting` model. Minimal work needed.

- Validate jobs fetch keys from correct business context
- Add test: "keys from different businesses don't cross-contaminate"
- Add onboarding step for key configuration

---

## Phase 4: Prospecting Playbooks (Weeks 4-6)

### 4.1 Data Models

```
playbooks:
  id, business_id (nullable for system playbooks), name, description, vertical,
  is_system, is_custom, filter_logic (json), scoring_weights (json),
  created_at, updated_at

playbook_email_templates:
  id, playbook_id, subject, body, cta_text,
  suggested_outreach_sequence (json), created_at
```

### 4.2 Pre-Built Vertical Playbooks (seeded)

**Web Dev Agency:**
- Filter: `website IS NULL OR website = ''` AND `review_rating >= 4.0`
- Scoring: +40 no website, +20 high rating, -10 large company
- Template: "Noticed you don't have a site yet..."

**Marketing Agency:**
- Filter: `website EXISTS` AND `website_last_updated < 6 months` AND active social
- Scoring: outdated site weight, social activity weight
- Template: "Your social is thriving but your site could match..."

**Insurance Broker:**
- Filter: `year_founded >= current_year - 1` AND `review_count < 5`
- Scoring: new business + low reviews = high propensity
- Template: "Congrats on launching..."

### 4.3 Filament Resource

- `PlaybookResource` with form sections:
  1. Basic info (name, description, vertical)
  2. Filter builder (reuse lead filter patterns)
  3. Scoring weights (sliders per field)
  4. Email template picker
  5. Outreach sequence config

### 4.4 Apply Playbook to Leads

- `ApplyPlaybookToLeads` Livewire component (modal)
- Preview matching lead count before applying
- `ApplyPlaybookToLeadsJob` — async scoring, tagging, status updates
- Activity trail logging

---

## Phase 5: Lead Source Integrations (Weeks 5-7)

### 5.1 Google Places API

- `GooglePlacesService` — search by location + business type
- `SearchAndImportGooglePlacesJob` — queued import from results
- Filament page: `SearchLeads` with location autocomplete, type selector, preview results
- Tier-gated: Team+ only

### 5.2 Future Integrations (roadmap)

- Yelp Business API
- Industry directories
- Data enrichment (Apollo, Clearbit, Hunter.io)

---

## Phase 6: Automation Rules (Weeks 6-8)

### 6.1 Data Model

```
automation_rules:
  id, business_id, name, is_enabled, trigger, conditions (json),
  actions (json), execution_count, last_executed_at, created_at, updated_at

automation_rule_logs:
  id, rule_id, lead_id, triggered_at, conditions_met (json),
  actions_executed (json), status, error_message, created_at
```

### 6.2 Triggers, Conditions, Actions

**Triggers:** lead.imported, lead.reply_received, lead.score_changed, lead.status_changed, lead.tag_added

**Conditions:** lead.score > X, lead.status == X, lead.has_tag(X), lead.created_at > X days ago

**Actions:** assign_to_user, change_status, add_tag, send_notification, trigger_email_draft

### 6.3 Filament Resource

- Form-based rule builder (no visual canvas yet)
- Trigger selector → condition builder → action selector
- Execution history table

### 6.4 Execution Engine

- Hook into existing Laravel events (LeadImported, LeadReplied, etc.)
- `ExecuteAutomationRulesJob` dispatched on event
- Evaluate conditions → execute actions → log results

---

## Phase 7: Onboarding & Landing Page (Weeks 8-10)

### 7.1 Public Marketing Site

- Landing page: hero, features, pricing, CTA
- Pricing page: interactive tier comparison
- Blade templates + Tailwind (same stack)

### 7.2 Sign-Up & Payment Flow

1. Choose plan (Solo / Team / Agency)
2. Create account (name, email, password)
3. Stripe Checkout for paid tiers
4. Redirect to onboarding

### 7.3 First-Run Onboarding

Multi-step Livewire component:
1. Business profile (name, industry, services)
2. AI key setup (BYOK)
3. Import first leads (CSV or Google Places search)
4. Generate first email draft (walkthrough)
5. Dashboard

---

## Phase 8: API Access (Weeks 10-11, Agency only)

- Token-based auth: `allleads_sk_<random>`, hashed storage
- Endpoints: leads CRUD, playbook apply, email send, usage stats
- Rate limiting, scoped to business
- Agency tier gate

---

## Phase 9: White-Label Email (Week 12, Agency only)

- Custom sender email/name in `AiSetting`
- Domain verification walkthrough (DKIM/SPF)
- Update `SendEmailJob` to use white-label config

---

## Phase 10: Testing & Polish (Weeks 12-13)

- Feature tests for subscriptions, gates, playbooks, automations, API
- Pint formatting pass
- Larastan static analysis
- Documentation

---

## Dependency Graph

```
Phase 1 (Cashier) ──┬──→ Phase 2 (Gates) ──→ All feature phases
                    ├──→ Phase 3 (BYOK validation, parallel)
                    ├──→ Phase 4 (Playbooks)
                    ├──→ Phase 5 (Google Places)
                    ├──→ Phase 6 (Automation)
                    ├──→ Phase 7 (Landing + Onboarding)
                    ├──→ Phase 8 (API)
                    └──→ Phase 9 (White-Label)

Phase 7 (Onboarding) ──→ Phase 10 (Docs + Tests)
```

**Parallel work:** Phases 1 & 3 | Phases 4, 5, 6 overlap | Phase 7 starts once Phase 1 done

---

## Key Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Lead limit enforcement complexity | Denormalize counts to businesses table, refresh on import |
| Subscription webhook sync delays | Database queue is synchronous enough, test retry logic |
| Google Places rate limits | Queued jobs with exponential backoff |
| Trial abuse | Email verification + optional CC requirement |
| Playbook complexity creep | Start with 3 pre-built, simple custom builder |
| Onboarding drop-off | Make skippable, show dashboard immediately |
