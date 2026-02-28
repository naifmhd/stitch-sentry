# StitchSentry (working name)

Premium Embroidery QA + Batch Personalization SaaS.

Built with **Laravel + Inertia (Vue 3 + TypeScript) + Tailwind**, **MySQL**, **Redis queues + Horizon**, and **Laravel Reverb** for realtime, premium UX.

---

## What this repo provides

- **Embroidery QA Checker** (upload → parse → render → rules → AI summary → PDF)
- **Batch Personalization** (template + CSV → proofs → approvals → export pack)
- **Paywall** (plans + credits)
- **Pluggable LLM providers** (OpenAI / Gemini / Anthropic) with per-org keys
- **Full automation** via queues and realtime UI updates via Reverb

---

## Tech stack

- PHP 8.3+
- Laravel 11/12
- Inertia.js + Vue 3 + TypeScript
- TailwindCSS
- MySQL 8
- Redis
- Laravel Horizon
- Laravel Reverb
- Storage: S3-compatible (S3 / DO Spaces / Cloudflare R2)
- Optional: Python microservice for embroidery parsing/rendering (recommended)

---

## Suggested repository structure

```
app/
  Domain/
    Qa/
      Actions/
      Rules/
      DTO/
      Services/
    Batch/
      Actions/
      DTO/
      Services/
    Billing/
      Services/
    Llm/
      Contracts/
      Providers/
      Services/
  Http/Controllers/
  Jobs/
  Events/
  Policies/
resources/js/
  Pages/
  Components/
  Composables/
docs/
  dev/
  ai/
```

---

## Local development setup

### 1) Requirements

- PHP 8.3+ (pdo_mysql, redis, mbstring, openssl, bcmath, intl, gd)
- Composer
- Node 20+
- MySQL 8
- Redis

### 2) Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 3) Configure `.env` (minimum)

```env
APP_NAME=StitchSentry
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stitchsentry
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Horizon
HORIZON_PREFIX=stitchsentry_horizon:

# Reverb (local)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=localkey
REVERB_APP_SECRET=localsecret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Storage (local filesystem for dev; switch to S3/R2 later)
FILESYSTEM_DISK=public

# LLM providers (platform keys; orgs can override in-app)
LLM_DEFAULT_PROVIDER=openai
OPENAI_API_KEY=
GEMINI_API_KEY=
ANTHROPIC_API_KEY=

# Parser service (recommended)
PARSER_SERVICE_URL=http://127.0.0.1:9001
PARSER_SERVICE_SECRET=dev-secret
```

### 4) Create database

Create a MySQL DB named `stitchsentry` and set credentials in `.env`.

### 5) Migrate + seed

```bash
php artisan migrate
php artisan db:seed
```

### 6) Run the app (4 terminals)

**Terminal A — Laravel**

```bash
php artisan serve
```

**Terminal B — Vite**

```bash
npm run dev
```

**Terminal C — Horizon**

```bash
php artisan horizon
```

**Terminal D — Reverb**

```bash
php artisan reverb:start
```

Open: http://localhost:8000

---

## Queue pipelines

### QA run pipeline

1. Upload → create `design_files`
2. `CreateQaRunJob` (queue: `ingest`)
3. `ParseEmbroideryFileJob` (queue: `parse`)
4. `RenderPreviewsJob` (queue: `render`)
5. `RunRuleQaJob` (queue: `qa`)
6. `GenerateAiSummaryJob` (queue: `ai`) **(paywalled)**
7. `GeneratePdfReportJob` (queue: `pdf`) **(paywalled)**
8. Broadcast progress via Reverb at each step

### Batch pipeline

1. Create `batch_runs`
2. Validate CSV
3. `ProcessBatchRunJob` (queue: `export`)
4. Per item: proof + optional QA + approval token
5. `GenerateBatchExportZipJob` (queue: `export`)

---

## Pluggable LLM providers

All AI calls go through the router abstraction:

- Per-org provider selection
- Per-org API keys (encrypted at rest)
- Fallback chain (e.g., OpenAI → Gemini → rules-only)

---

## Paywall (feature gating)

Feature gating is enforced server-side using `FeatureGate`:

- AI summaries
- PDF export
- Presets
- Batch processing
- Batch exports
- Higher daily limits

---

## License

Proprietary (replace as needed).
