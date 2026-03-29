# AllLeads CRM — Brevo Email Setup

This guide covers everything needed to configure Brevo (formerly Sendinblue) for transactional email sending and inbound reply tracking.

---

## Table of Contents

1. [Create a Brevo Account](#1-create-a-brevo-account)
2. [Verify a Sending Domain](#2-verify-a-sending-domain)
3. [Get Your API Key](#3-get-your-api-key)
4. [Get Your SMTP Credentials](#4-get-your-smtp-credentials)
5. [Configure Inbound Email (Reply Tracking)](#5-configure-inbound-email-reply-tracking)
6. [Set Up the Inbound Webhook](#6-set-up-the-inbound-webhook)
7. [Configure the App](#7-configure-the-app)
8. [Per-User Sender Addresses](#8-per-user-sender-addresses)
9. [Testing the Integration](#9-testing-the-integration)
10. [Brevo Limits & Free Tier](#10-brevo-limits--free-tier)

---

## 1. Create a Brevo Account

1. Go to https://app.brevo.com and sign up (free plan available)
2. Verify your email address
3. Complete the onboarding — choose **Transactional emails** as your primary use case

---

## 2. Verify a Sending Domain

You must verify the domain you'll send from (e.g. `nsh.one`) so emails don't land in spam.

### 2.1 Add the domain

1. Go to **Brevo → Settings → Senders & IP → Domains**
2. Click **Add a domain**
3. Enter your domain (e.g. `nsh.one`)
4. Brevo will display a set of DNS records to add

### 2.2 Add DNS records

Add all records Brevo shows to your DNS provider. Typically:

| Type | Name | Value | Purpose |
|---|---|---|---|
| TXT | `@` or `nsh.one` | `brevo-code:xxxxxxxx` | Domain ownership |
| TXT | `mail._domainkey` | `v=DKIM1; k=rsa; p=...` | DKIM signing |
| TXT | `@` | `v=spf1 include:spf.brevo.com ~all` | SPF |
| CNAME | `mail` | `mail.brevo.com` | (optional, for tracking links) |

> DNS propagation can take up to 24 hours. Brevo provides a "Verify" button that checks live.

### 2.3 Verify DMARC (recommended)

Add a DMARC record to protect your domain:

```
Type: TXT
Name: _dmarc
Value: v=DMARC1; p=none; rua=mailto:dmarc@nsh.one
```

Start with `p=none` (monitor mode) and tighten to `p=quarantine` once you confirm only Brevo sends on your behalf.

---

## 3. Get Your API Key

The app uses Brevo's **Transactional API** to send emails programmatically.

1. Go to **Brevo → Settings → API Keys**
2. Click **Generate a new API key**
3. Name it `allleads-production` (create a separate one for staging)
4. Copy the key — it's shown only once

Add it to your environment:

```dotenv
BREVO_API_KEY=xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxx
```

> Create separate API keys for staging and production. This lets you revoke one without affecting the other.

---

## 4. Get Your SMTP Credentials

The app also supports sending via Brevo SMTP (used by Laravel's `Mail` facade as a fallback / bulk path).

1. Go to **Brevo → Settings → SMTP & API → SMTP**
2. Note your credentials:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-brevo-login-email@example.com
MAIL_PASSWORD=your-smtp-key              # NOT your account password — use the SMTP key shown here
MAIL_FROM_ADDRESS=noreply@nsh.one
MAIL_FROM_NAME="AllLeads CRM"
```

> The **SMTP key** is different from the API key. Find it under Settings → SMTP & API.

---

## 5. Configure Inbound Email (Reply Tracking)

To automatically capture replies from leads, Brevo's **Inbound Parsing** feature forwards incoming emails to your app as a webhook payload.

### 5.1 Set up an inbound email address

You need a domain or subdomain that routes mail to Brevo's inbound servers.

**Option A — Use a subdomain you control (recommended)**

Add an MX record pointing to Brevo's inbound servers:

```
Type: MX
Name: inbound.nsh.one   (or replies.nsh.one)
Value: inbound.brevo.com
Priority: 10
TTL: 3600
```

All emails sent to `*@inbound.nsh.one` will be forwarded to your webhook.

**Option B — Use Brevo's shared inbound domain**

Brevo provides a shared inbound address (`@inbound.brevo.com`) without DNS setup, but it is less reliable for production use.

### 5.2 Why this works for reply tracking

When AllLeads sends an outbound email it sets:

```
Reply-To: replies+{thread_id}@inbound.nsh.one
```

When a lead hits "Reply", their email client sends to that address. Brevo receives it and POSTs the parsed content to your webhook.

---

## 6. Set Up the Inbound Webhook

### 6.1 Register the webhook in Brevo

1. Go to **Brevo → Settings → Inbound Parsing**
2. Click **Add an inbound parsing rule**
3. Set:
   - **Domain / Email**: `inbound.nsh.one` (or the address from step 5)
   - **Destination URL**: `https://allleads.nsh.one/webhooks/brevo/inbound`
   - **Save raw email**: Yes (recommended for debugging)
4. Save

### 6.2 Generate a webhook secret

The app verifies all incoming webhook calls using a shared secret to prevent spoofed requests.

Generate a random secret:

```bash
openssl rand -hex 32
```

Copy the output and set it in both the app and Brevo:

- **App env**: `BREVO_WEBHOOK_SECRET=your-generated-secret`
- **Brevo**: Under your inbound rule → **Secret token** field (if available), or store it as a custom header value

> If Brevo's inbound webhook does not support a secret token header, the app falls back to IP allowlisting. Brevo's inbound webhook IPs are published at https://developers.brevo.com/docs/inbound-parsing.

### 6.3 What the webhook receives

Brevo POSTs a JSON payload to your endpoint with fields including:

```json
{
  "From": "lead@example.com",
  "To": "replies+abc123@inbound.nsh.one",
  "Subject": "Re: Your web design proposal",
  "HtmlBody": "<p>Thanks, I'm interested...</p>",
  "TextBody": "Thanks, I'm interested...",
  "MessageId": "<xxxx@mail.example.com>",
  "InReplyTo": "<original-message-id@brevo.com>",
  "Headers": { ... }
}
```

The app extracts the `thread_id` from the `To` address, matches it to an `EmailThread`, creates a new `EmailMessage`, and triggers the `LeadRepliedEvent`.

---

## 7. Configure the App

### 7.1 Full `.env` block for Brevo

```dotenv
# --- Brevo Transactional API ---
BREVO_API_KEY=xkeysib-...

# --- Brevo Inbound Webhook ---
BREVO_WEBHOOK_SECRET=your-generated-secret

# --- Laravel Mail (SMTP via Brevo) ---
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-brevo-email@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS=noreply@nsh.one
MAIL_FROM_NAME="AllLeads CRM"

# --- Reply-To routing ---
BREVO_INBOUND_DOMAIN=inbound.nsh.one
```

### 7.2 K8s secret

Add the Brevo values to the cluster secret (see `DEPLOY.md` § 4.5):

```bash
kubectl patch secret allleads-secrets -n allleads-production \
  --type='json' \
  -p='[
    {"op":"add","path":"/data/BREVO_API_KEY","value":"'$(echo -n "xkeysib-..." | base64)'"},
    {"op":"add","path":"/data/BREVO_WEBHOOK_SECRET","value":"'$(echo -n "your-secret" | base64)'"},
    {"op":"add","path":"/data/BREVO_INBOUND_DOMAIN","value":"'$(echo -n "inbound.nsh.one" | base64)'"},
    {"op":"add","path":"/data/MAIL_USERNAME","value":"'$(echo -n "your@email.com" | base64)'"},
    {"op":"add","path":"/data/MAIL_PASSWORD","value":"'$(echo -n "your-smtp-key" | base64)'"}
  ]'
kubectl rollout restart deployment/allleads-app -n allleads-production
```

---

## 8. Per-User Sender Addresses

Each agent in AllLeads can configure their own sending identity in **Profile → Email Settings**:

| Field | Description |
|---|---|
| Sender Name | e.g. `Ivan Petrov` |
| Sender Email | e.g. `ivan@nsh.one` — must be a verified sender in Brevo |
| Reply-To | Defaults to the inbound routing address; can override |
| Default CC | Always CC'd on outbound emails |
| Default BCC | Silent copy (e.g. a CRM archive address) |
| Email Header | Image displayed at top of email HTML |
| Signature | Rich-text; supports `{{user_name}}`, `{{user_email}}`, `{{company_name}}` |

### Adding agent sender addresses in Brevo

Each email address an agent sends from must be verified in Brevo:

1. **Brevo → Settings → Senders & IP → Senders**
2. Click **Add a sender**
3. Enter the agent's name and email
4. Brevo sends a verification email to that address — the agent must click the link
5. Once verified, the address can be used in AllLeads

> If all agents share the same domain (e.g. `@nsh.one`) and you've verified the domain in step 2, individual addresses on that domain are automatically trusted — no per-sender verification needed.

---

## 9. Testing the Integration

### 9.1 Test outbound sending

```bash
# Locally — check the log driver output
make artisan CMD="tinker"
# In tinker:
Mail::raw('Test email', fn($m) => $m->to('test@example.com')->subject('Test'));

# Or trigger from the UI: create a lead, generate a draft, send it
```

### 9.2 Test the inbound webhook locally

Use [ngrok](https://ngrok.com) or [expose](https://expose.dev) to tunnel your local app:

```bash
ngrok http 8080
# → https://abc123.ngrok.io
```

Then temporarily update your Brevo inbound rule destination URL to `https://abc123.ngrok.io/webhooks/brevo/inbound`.

Send a test reply to your inbound address and watch the logs:

```bash
make logs SVC=app
# Look for: [BrevoInboundController] Received inbound email for thread: <id>
```

### 9.3 Simulate a webhook payload manually

```bash
curl -X POST https://allleads.nsh.one/webhooks/brevo/inbound \
  -H "Content-Type: application/json" \
  -H "X-Brevo-Signature: $(echo -n '{"From":"lead@test.com"}' | openssl dgst -sha256 -hmac 'your-webhook-secret' | awk '{print $2}')" \
  -d '{
    "From": "lead@test.com",
    "To": "replies+THREAD_ID_HERE@inbound.nsh.one",
    "Subject": "Re: Your proposal",
    "TextBody": "Hi, I am interested!",
    "HtmlBody": "<p>Hi, I am interested!</p>",
    "MessageId": "<test-123@mail.test.com>",
    "InReplyTo": "<original-id@brevo.com>"
  }'
```

---

## 10. Brevo Limits & Free Tier

| Plan | Daily sending limit | Monthly emails | Inbound | API access |
|---|---|---|---|---|
| **Free** | 300/day | 9,000/month | ✓ | ✓ |
| **Starter** (~€25/mo) | Unlimited | 20,000/month | ✓ | ✓ |
| **Business** (~€65/mo) | Unlimited | 20,000+/month | ✓ | ✓ + advanced analytics |

> For a small agency sending cold outreach to ~100–200 leads/day, the **Free** plan is sufficient to start. Upgrade to **Starter** once daily volume exceeds 300.

### Staying within free tier limits

- The app's campaign creation modal displays estimated email count before queuing
- `SendEmailJob` respects a configurable `BREVO_DAILY_LIMIT` env var — jobs beyond the limit are delayed to the next day
- Brevo's dashboard → **Statistics → Transactional** shows real-time delivery, open, and bounce rates

### Bounce & complaint handling

Brevo automatically suppresses addresses that bounce or mark as spam. The app's `BrevoWebhookController` also handles Brevo's **transactional webhook** events (`hard_bounce`, `spam`, `unsubscribed`) to update the lead's status to `disqualified` and log an activity entry.

Add the transactional event webhook in Brevo:

1. Go to **Brevo → Settings → Webhooks → Transactional**
2. Add URL: `https://allleads.nsh.one/webhooks/brevo/events`
3. Select events: `hard_bounce`, `soft_bounce`, `spam`, `unsubscribed`, `delivered`
