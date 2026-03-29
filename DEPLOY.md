# AllLeads CRM — Deployment Guide

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Local Development Setup](#2-local-development-setup)
3. [Environment Variables Reference](#3-environment-variables-reference)
4. [DigitalOcean Kubernetes — First-Time Setup](#4-digitalocean-kubernetes--first-time-setup)
   - 4.1 [Create the K8s Cluster](#41-create-the-k8s-cluster)
   - 4.2 [Managed MySQL Database](#42-managed-mysql-database)
   - 4.3 [Container Registry (GHCR)](#43-container-registry-ghcr)
   - 4.4 [Install Cluster Add-ons](#44-install-cluster-add-ons)
   - 4.5 [Create Namespaces & Secrets](#45-create-namespaces--secrets)
   - 4.6 [First Manual Deploy](#46-first-manual-deploy)
5. [GitHub Actions CI/CD](#5-github-actions-cicd)
   - 5.1 [Repository Secrets](#51-repository-secrets)
   - 5.2 [Workflow Overview](#52-workflow-overview)
   - 5.3 [Staging Deploys](#53-staging-deploys)
   - 5.4 [Production Releases](#54-production-releases)
6. [K8s Manifest Structure](#6-k8s-manifest-structure)
7. [Ongoing Operations](#7-ongoing-operations)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Prerequisites

### Local machine

| Tool | Min version | Install |
|---|---|---|
| Docker Desktop | 26+ | https://docs.docker.com/get-docker/ |
| Docker Compose | v2 (bundled) | bundled with Docker Desktop |
| GNU Make | any | `brew install make` |
| PHP | 8.4 | `brew install php` (only needed outside Docker) |
| Composer | 2.x | `brew install composer` |
| Node.js | 20+ | `brew install node` |
| `git` | any | pre-installed on macOS |

### For DigitalOcean / K8s operations

| Tool | Install |
|---|---|
| `doctl` (DigitalOcean CLI) | `brew install doctl` |
| `kubectl` | `brew install kubectl` |
| `helm` | `brew install helm` |
| `kustomize` | `brew install kustomize` |
| `gh` (GitHub CLI) | `brew install gh` |

---

## 2. Local Development Setup

### 2.1 Clone & configure

```bash
git clone https://github.com/YOUR_ORG/allleads.git
cd allleads

cp .env.example .env
```

Open `.env` and fill in the values marked `# REQUIRED`. At minimum for local dev:

```dotenv
APP_NAME=AllLeads
APP_ENV=local
APP_KEY=                   # generated below
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=allleads
DB_USERNAME=allleads
DB_PASSWORD=secret

MAIL_MAILER=log          # emails written to storage/logs/laravel.log locally
```

> API keys for Brevo, OpenRouter, Groq, and Gemini can be left empty locally — the app will run but AI and email features will return validation errors until keys are provided.

### 2.2 Start the stack

```bash
make up
```

This runs `docker compose up -d --build` and starts:
- `app` — PHP 8.4-FPM
- `nginx` — http://localhost:8080
- `mysql` — MySQL 8, port 3306 (mirrors DO Managed MySQL in production)
- `queue` — Laravel queue worker (`php artisan queue:work`)
- `scheduler` — runs `php artisan schedule:run` in a loop every 60 seconds

### 2.3 First-time initialisation

```bash
make init
```

This is equivalent to running the following in sequence (you can also run them individually):

```bash
make artisan CMD="key:generate"
make artisan CMD="migrate --seed"
```

This runs all migrations and seeds:
- Creates the admin user: **admin@allleads.dev** / **password** (change immediately in production)
- Seeds a demo agent, tags, and 20 sample leads

### 2.4 Access the app

| URL | What |
|---|---|
| http://localhost:8080 | Main app |
| http://localhost:8080/admin | Filament admin panel |

> Outbound emails in local dev are written to `storage/logs/laravel.log` (driver: `log`). Set real Brevo credentials in `.env` to test actual sending.

### 2.5 Useful Make targets

```bash
make up            # Start all containers (build if needed)
make down          # Stop all containers
make restart       # down + up
make logs          # Tail all container logs
make logs SVC=app  # Tail a specific service

make artisan CMD="route:list"          # Run any artisan command
make composer CMD="require some/pkg"   # Run composer inside the container
make npm CMD="run dev"                 # Run npm inside the container

make migrate       # Run migrations
make seed          # Run seeders
make fresh         # migrate:fresh --seed (drops all tables)

make test          # Run full Pest test suite
make lint          # Run Laravel Pint (fixer)
make analyse       # Run Larastan static analysis

make shell         # bash shell inside the app container
make tinker        # Laravel Tinker inside the container
```

### 2.6 Hot-reloading assets

Open a second terminal:

```bash
make npm CMD="run dev"
```

Vite serves with HMR at `http://localhost:5173`. The app container proxies asset requests automatically via the `VITE_DEV_SERVER_URL` env var.

### 2.7 Running queue workers & scheduler locally

Both `queue` and `scheduler` containers start automatically with `make up`.

To restart them individually:

```bash
docker compose restart queue
docker compose restart scheduler
```

To tail their logs:

```bash
make logs SVC=queue
make logs SVC=scheduler
```

To inspect pending/failed jobs:

```bash
make artisan CMD="queue:monitor"
make artisan CMD="queue:failed"
```

---

## 3. Environment Variables Reference

A complete `.env.example` ships with the repo. Key variables grouped by concern:

### App
```dotenv
APP_NAME=AllLeads
APP_ENV=local|staging|production
APP_KEY=                    # php artisan key:generate
APP_DEBUG=true|false
APP_URL=https://allleads.nsh.one
```

### Database
```dotenv
DB_CONNECTION=mysql
DB_HOST=                    # DO Managed DB hostname in production
DB_PORT=25060               # DO Managed DB port (TLS)
DB_DATABASE=allleads
DB_USERNAME=allleads
DB_PASSWORD=
DB_SSL_MODE=require         # for DO Managed DB
```

### Queue & Cache
```dotenv
QUEUE_CONNECTION=database   # jobs stored in MySQL 'jobs' table — no Redis needed
CACHE_DRIVER=database       # cache stored in MySQL 'cache' table
SESSION_DRIVER=database     # sessions stored in MySQL 'sessions' table
```

### Email (Brevo)
```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=              # your Brevo login email
MAIL_PASSWORD=              # Brevo SMTP key
MAIL_FROM_ADDRESS=noreply@nsh.one
MAIL_FROM_NAME=AllLeads

BREVO_API_KEY=              # for Transactional API calls
BREVO_WEBHOOK_SECRET=       # for verifying inbound webhooks
```

### AI Providers
```dotenv
OPENROUTER_API_KEY=
GROQ_API_KEY=
GEMINI_API_KEY=
```

### Broadcasting (Reverb)
```dotenv
BROADCAST_DRIVER=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Admin Bootstrap
```dotenv
ADMIN_EMAIL=admin@allleads.dev
ADMIN_PASSWORD=changeme
```

---

## 4. DigitalOcean Kubernetes — First-Time Setup

> **Do this once** before automated deployments via GitHub Actions can work.

### 4.1 Create the K8s Cluster

```bash
# Authenticate doctl
doctl auth init
# → paste your DigitalOcean personal access token

# Create a cluster (adjust region/size to your preference)
doctl kubernetes cluster create allleads \
  --region ams3 \
  --node-pool "name=workers;size=s-2vcpu-4gb;count=2;auto-scale=true;min-nodes=2;max-nodes=5" \
  --version latest \
  --wait

# Merge kubeconfig
doctl kubernetes cluster kubeconfig save allleads

# Verify
kubectl get nodes
```

> **Recommended node size:** `s-2vcpu-4gb` (2 vCPU / 4 GB) per node, 2 nodes minimum.
> For production-grade, upgrade to `s-4vcpu-8gb` and set min=2, max=8.

### 4.2 Managed MySQL Database

```bash
# Create a MySQL 8 cluster
doctl databases create allleads-mysql \
  --engine mysql \
  --version 8 \
  --size db-s-1vcpu-1gb \
  --region ams3 \
  --num-nodes 1

# Get the connection details
doctl databases connection allleads-mysql --format Host,Port,User,Password,Database
```

Once created:
1. Go to DigitalOcean dashboard → Databases → `allleads-mysql`
2. **Create a database** named `allleads`
3. **Create a user** named `allleads` — note the auto-generated password
4. Under **Trusted Sources**, add your K8s cluster so the app pods can connect
5. Download the **CA certificate** — you'll need it for `DB_SSL_CA` if you choose certificate-based TLS

> No Redis is needed. Queues, cache, and sessions all use the MySQL `database` driver.

### 4.3 Container Registry (GHCR)

The CI pipeline pushes images to **GitHub Container Registry (GHCR)**. No setup needed beyond authenticating — the GitHub Actions workflow uses `GITHUB_TOKEN` automatically.

To pull images on the cluster you need to create an image pull secret:

```bash
# Create a GitHub Personal Access Token with read:packages scope
# https://github.com/settings/tokens

kubectl create secret docker-registry ghcr-pull-secret \
  --docker-server=ghcr.io \
  --docker-username=YOUR_GITHUB_USERNAME \
  --docker-password=YOUR_GITHUB_PAT \
  --docker-email=your@email.com \
  --namespace allleads-staging

kubectl create secret docker-registry ghcr-pull-secret \
  --docker-server=ghcr.io \
  --docker-username=YOUR_GITHUB_USERNAME \
  --docker-password=YOUR_GITHUB_PAT \
  --docker-email=your@email.com \
  --namespace allleads-production
```

### 4.4 Install Cluster Add-ons

#### ingress-nginx

```bash
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update

helm install ingress-nginx ingress-nginx/ingress-nginx \
  --namespace ingress-nginx \
  --create-namespace \
  --set controller.publishService.enabled=true
```

Get the external IP (may take 1–2 minutes):

```bash
kubectl get svc -n ingress-nginx ingress-nginx-controller \
  --output jsonpath='{.status.loadBalancer.ingress[0].ip}'
```

Point your DNS records at this IP:
- `allleads.nsh.one` → A record → `<EXTERNAL_IP>`
- `staging.allleads.nsh.one` → A record → `<EXTERNAL_IP>`

#### cert-manager (TLS via Let's Encrypt)

```bash
helm repo add jetstack https://charts.jetstack.io
helm repo update

helm install cert-manager jetstack/cert-manager \
  --namespace cert-manager \
  --create-namespace \
  --set installCRDs=true
```

Create a ClusterIssuer for Let's Encrypt (save as `k8s/cluster-issuer.yaml` and apply once):

```yaml
# k8s/cluster-issuer.yaml
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: your@email.com          # ← change this
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
      - http01:
          ingress:
            class: nginx
```

```bash
kubectl apply -f k8s/cluster-issuer.yaml
```

### 4.5 Create Namespaces & Secrets

#### Create namespaces

```bash
kubectl create namespace allleads-staging
kubectl create namespace allleads-production
```

#### Create app secrets

Create secrets for both namespaces. Repeat for each namespace, adjusting values:

```bash
# Helper — run this block for EACH namespace:
NAMESPACE=allleads-staging   # change to allleads-production for the other

kubectl create secret generic allleads-secrets \
  --namespace $NAMESPACE \
  --from-literal=APP_KEY="base64:$(openssl rand -base64 32)" \
  --from-literal=DB_HOST="your-do-mysql-host.db.ondigitalocean.com" \
  --from-literal=DB_PORT="25060" \
  --from-literal=DB_DATABASE="allleads" \
  --from-literal=DB_USERNAME="allleads" \
  --from-literal=DB_PASSWORD="your-db-password" \
  --from-literal=BREVO_API_KEY="your-brevo-key" \
  --from-literal=BREVO_WEBHOOK_SECRET="your-webhook-secret" \
  --from-literal=OPENROUTER_API_KEY="your-openrouter-key" \
  --from-literal=GROQ_API_KEY="your-groq-key" \
  --from-literal=GEMINI_API_KEY="your-gemini-key" \
  --from-literal=ADMIN_EMAIL="admin@allleads.dev" \
  --from-literal=ADMIN_PASSWORD="change-me-immediately"
```

> **Tip:** Store these values in a password manager or DigitalOcean Secrets. Never commit them to the repo.

#### Verify

```bash
kubectl get secrets -n allleads-staging
kubectl get secrets -n allleads-production
```

### 4.6 First Manual Deploy

Before GitHub Actions takes over, deploy manually to verify everything works.

```bash
# Build and push the image locally
docker build -f docker/php/Dockerfile -t ghcr.io/YOUR_ORG/allleads:latest .
docker push ghcr.io/YOUR_ORG/allleads:latest

# Deploy to staging
kustomize build k8s/overlays/staging | kubectl apply -f -

# Check rollout
kubectl rollout status deployment/allleads-app -n allleads-staging
kubectl get pods -n allleads-staging

# Run migrations on staging
kubectl exec -n allleads-staging \
  $(kubectl get pod -n allleads-staging -l app=allleads -o jsonpath='{.items[0].metadata.name}') \
  -- php artisan migrate --force

# Run seeds (first time only)
kubectl exec -n allleads-staging \
  $(kubectl get pod -n allleads-staging -l app=allleads -o jsonpath='{.items[0].metadata.name}') \
  -- php artisan db:seed --force
```

Visit `https://allleads-staging.nsh.one/admin` — you should see the Filament login.

---

## 5. GitHub Actions CI/CD

### 5.1 Repository Secrets

Go to **GitHub → Your repo → Settings → Secrets and variables → Actions** and add:

| Secret name | Value |
|---|---|
| `GHCR_TOKEN` | GitHub PAT with `write:packages` scope |
| `DO_KUBECONFIG` | Base64-encoded kubeconfig — see below |
| `APP_KEY_STAGING` | The APP_KEY used in staging K8s secret |
| `APP_KEY_PRODUCTION` | The APP_KEY used in production K8s secret |

#### Generating `DO_KUBECONFIG`

```bash
# Save kubeconfig for the allleads cluster
doctl kubernetes cluster kubeconfig show allleads | base64 | tr -d '\n'
# → copy the output and paste as DO_KUBECONFIG secret
```

> The CI workflow saves this to a temp file and sets `KUBECONFIG` env var so `kubectl` works in the runner.

### 5.2 Workflow Overview

```
.github/workflows/
├── ci.yml       — runs on every push and PR
└── deploy.yml   — runs on push to main (staging) and release tags (production)
```

#### `ci.yml` — Test & Lint

```
Trigger: push to any branch, pull_request

Jobs:
  test:
    - Checkout
    - Setup PHP 8.4 + extensions
    - composer install --no-dev
    - php artisan key:generate
    - Run migrations against SQLite in-memory
    - vendor/bin/pest
    - vendor/bin/pint --test      (lint check)
    - vendor/bin/phpstan analyse  (Larastan)
```

#### `deploy.yml` — Build & Deploy

```
Trigger:
  - push to main         → deploys to allleads-staging
  - release published    → deploys to allleads-production

Jobs:
  build:
    - docker build → tag with git SHA + branch/tag
    - docker push to ghcr.io/YOUR_ORG/allleads

  deploy-staging:
    needs: build
    if: github.ref == 'refs/heads/main'
    - kustomize edit set image → new SHA tag
    - kubectl apply
    - kubectl rollout status (timeout 5m)
    - kubectl exec artisan migrate --force

  deploy-production:
    needs: build
    if: github.event_name == 'release'
    - same as staging but namespace = allleads-production
    - requires manual approval (GitHub Environment protection rule)
```

### 5.3 Staging Deploys

Every merge to `main`:

1. CI tests run
2. Docker image built and tagged `:main-<sha>`
3. `kustomize` patches the image tag in the staging overlay
4. `kubectl apply` rolls out new pods
5. Zero-downtime rolling update (2 replicas, `maxUnavailable: 0`)
6. `artisan migrate --force` runs in a post-deploy init container

```bash
# Monitor a staging deploy from your machine
kubectl rollout status deployment/allleads-app -n allleads-staging --timeout=5m
kubectl logs -n allleads-staging -l app=allleads --tail=50 -f
```

### 5.4 Production Releases

To release to production:

```bash
# Tag and push
git tag v1.2.0
git push origin v1.2.0

# Then create a GitHub Release from the tag:
gh release create v1.2.0 --title "v1.2.0" --notes "Release notes here"
```

This triggers the production deploy job. If you have a **GitHub Environment** named `production` with a required reviewer, the job will pause for approval before applying.

Set up the protection rule:
> GitHub → Repo → Settings → Environments → `production` → Required reviewers → add yourself or a team

---

## 6. K8s Manifest Structure

```
k8s/
├── cluster-issuer.yaml          # ClusterIssuer (applied once, cluster-wide)
├── base/
│   ├── kustomization.yaml       # references all base resources
│   ├── deployment-app.yaml      # PHP-FPM app Deployment
│   ├── deployment-queue.yaml    # queue worker Deployment (php artisan queue:work)
│   ├── deployment-scheduler.yaml# scheduler Deployment (schedule:run loop)
│   ├── service.yaml             # ClusterIP services
│   ├── ingress.yaml             # Ingress (cert-manager annotations)
│   ├── configmap.yaml           # non-secret env vars (APP_ENV, QUEUE_CONNECTION=database, etc.)
│   └── hpa.yaml                 # HorizontalPodAutoscaler (app only)
└── overlays/
    ├── staging/
    │   ├── kustomization.yaml   # namespace: allleads-staging, image tag patch
    │   ├── ingress-patch.yaml   # host: allleads-staging.nsh.one
    │   └── replica-patch.yaml   # replicas: 1 (save cost on staging)
    └── production/
        ├── kustomization.yaml   # namespace: allleads-production, image tag patch
        ├── ingress-patch.yaml   # host: allleads.nsh.one
        └── replica-patch.yaml   # replicas: 2 (min), HPA max: 5
```

### Key deployment details

- **Readiness probe:** `GET /up` (Laravel's built-in health endpoint), `initialDelaySeconds: 10`
- **Liveness probe:** `GET /up`, `failureThreshold: 3`, `periodSeconds: 15`
- **Rolling update:** `maxSurge: 1`, `maxUnavailable: 0` — zero-downtime
- **Queue worker:** separate Deployment (`deployment-queue.yaml`), no readiness probe, restarts on failure, single replica is fine (scale up if job throughput demands it)
- **Scheduler:** separate Deployment (`deployment-scheduler.yaml`), single replica always, runs `while true; do php artisan schedule:run; sleep 60; done`
- **No CronJob / no Redis** — simpler ops; everything stored in MySQL
- **Resources (app pod):**
  ```yaml
  requests: { cpu: "100m", memory: "256Mi" }
  limits:   { cpu: "500m", memory: "512Mi" }
  ```
- **imagePullSecrets:** references `ghcr-pull-secret` in the namespace

---

## 7. Ongoing Operations

### View logs

```bash
# All app pods (streaming)
kubectl logs -n allleads-production -l app=allleads -f

# Queue worker
kubectl logs -n allleads-production -l app=allleads-queue -f

# A specific pod
kubectl logs -n allleads-production <pod-name>
```

### Run artisan commands in production

```bash
# Get a shell
kubectl exec -it -n allleads-production \
  $(kubectl get pod -n allleads-production -l app=allleads -o jsonpath='{.items[0].metadata.name}') \
  -- bash

# Or run a one-off command
kubectl exec -n allleads-production \
  $(kubectl get pod -n allleads-production -l app=allleads -o jsonpath='{.items[0].metadata.name}') \
  -- php artisan tinker
```

### Manual migration (emergency)

```bash
kubectl exec -n allleads-production \
  $(kubectl get pod -n allleads-production -l app=allleads -o jsonpath='{.items[0].metadata.name}') \
  -- php artisan migrate --force
```

### Update a secret

```bash
# Edit a secret value in-place
kubectl patch secret allleads-secrets -n allleads-production \
  --type='json' \
  -p='[{"op":"replace","path":"/data/BREVO_API_KEY","value":"'$(echo -n "new-key" | base64)'"}]'

# Restart pods to pick up the new value
kubectl rollout restart deployment/allleads-app -n allleads-production
```

### Scale manually

```bash
kubectl scale deployment allleads-app --replicas=3 -n allleads-production
```

### Rollback a bad deploy

```bash
# View rollout history
kubectl rollout history deployment/allleads-app -n allleads-production

# Rollback to previous revision
kubectl rollout undo deployment/allleads-app -n allleads-production

# Rollback to a specific revision
kubectl rollout undo deployment/allleads-app -n allleads-production --to-revision=3
```

### Storage — uploaded files

Email header images and imported files are stored in `storage/app`. In production, configure a **DigitalOcean Spaces** bucket (S3-compatible) so all pods share the same storage:

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=ams3
AWS_BUCKET=allleads-storage
AWS_ENDPOINT=https://ams3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

---

## 8. Troubleshooting

### Pods crash-looping

```bash
kubectl describe pod <pod-name> -n allleads-production
kubectl logs <pod-name> -n allleads-production --previous
```

Common causes: wrong DB credentials in secret, APP_KEY missing, PHP extension missing in image.

### 502 Bad Gateway from nginx ingress

1. Check the app pod is Running and Ready: `kubectl get pods -n allleads-production`
2. Check the service endpoints: `kubectl get endpoints -n allleads-production`
3. Check ingress: `kubectl describe ingress -n allleads-production`

### TLS certificate not issued

```bash
kubectl describe certificate -n allleads-production
kubectl describe certificaterequest -n allleads-production
kubectl logs -n cert-manager -l app=cert-manager | tail -50
```

Most common fix: DNS A record not yet propagated, or ingress-nginx external IP not stable yet.

### Database connection refused

- Ensure the K8s cluster is in the **Trusted Sources** for the DO Managed Database
- Check `DB_HOST`, `DB_PORT` match the DO connection string exactly (port is usually `25060` for MySQL over TLS)
- Confirm `DB_SSL_MODE=require` is set in the ConfigMap

### Queue jobs not processing

```bash
kubectl get pods -n allleads-production -l app=allleads-queue
kubectl logs -n allleads-production -l app=allleads-queue -f
```

If the queue worker pod is missing: `kubectl rollout restart deployment/allleads-queue -n allleads-production`

> Jobs are stored in the MySQL `jobs` table. You can inspect them directly:
> ```bash
> make artisan CMD="queue:failed"              # locally
> # or exec into a pod in production:
> kubectl exec -n allleads-production <pod> -- php artisan queue:failed
> ```

### GitHub Actions deploy fails: `kubectl: command not found`

The deploy workflow installs kubectl via the `azure/setup-kubectl` action. Check the workflow YAML has:
```yaml
- uses: azure/setup-kubectl@v3
- uses: azure/setup-kubelogin@v1
```

### GitHub Actions: image push fails to GHCR

Ensure the repo has `packages: write` permission in the workflow YAML:
```yaml
permissions:
  contents: read
  packages: write
```

---

## Quick Reference Cheatsheet

```bash
# --- LOCAL ---
make up && make init          # First-time local setup
make fresh                    # Reset DB and re-seed
make test                     # Run test suite
make shell                    # Shell inside app container
make logs SVC=queue           # Tail queue worker logs
make logs SVC=scheduler       # Tail scheduler logs

# --- K8s STAGING ---
kubectl get pods -n allleads-staging
kubectl logs -n allleads-staging -l app=allleads -f
kustomize build k8s/overlays/staging | kubectl apply -f -

# --- K8s PRODUCTION ---
kubectl get pods -n allleads-production
kubectl rollout status deployment/allleads-app -n allleads-production
kubectl rollout undo deployment/allleads-app -n allleads-production  # rollback

# --- RELEASE ---
git tag v1.0.0 && git push origin v1.0.0
gh release create v1.0.0 --title "v1.0.0"
```
