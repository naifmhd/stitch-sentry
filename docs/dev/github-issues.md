# GitHub Issues Backlog (Epics + Detailed Issues)

Use this doc to create GitHub Issues (one issue per heading). Each issue includes scope + acceptance criteria.

---

## Epic 0 — Foundation

### ISSUE 0.1 — Initialize Laravel + Inertia + Vue3 TS + Tailwind

**Description**
Set up Laravel with Inertia, Vue 3 + TS, Tailwind, and authentication scaffolding.

**Acceptance criteria**

- App boots locally (`php artisan serve`, `npm run dev`)
- Auth pages render via Inertia
- Base layout created with navigation shell

---

### ISSUE 0.2 — MySQL + baseline migrations/models

**Description**
Configure MySQL connection and create baseline migrations for:

- users
- organizations (stub)
- projects (stub)
- design_files (stub)

**Acceptance criteria**

- `php artisan migrate:fresh` succeeds
- Factories exist for core models

---

### ISSUE 0.3 — Redis queues + Horizon

**Description**
Configure Redis queue connection and Horizon dashboard. Create a test job and route to dispatch it.

**Acceptance criteria**

- Horizon shows the test job and processes it
- Logs confirm job executed

---

### ISSUE 0.4 — Laravel Reverb + Echo client

**Description**
Configure Reverb and front-end Echo subscription. Add a test page that receives a broadcast event.

**Acceptance criteria**

- User opens test page and receives realtime events without refresh

---

## Epic 1 — Organizations, Plans, Billing, Credits

### ISSUE 1.1 — Organizations + roles + org switcher

**Description**
Implement:

- organizations table
- organization_user pivot with role enum (owner/admin/member)
- policies and middleware to enforce org context
- UI: org switcher, org settings page

**Acceptance criteria**

- Owner can invite member
- Role enforcement works server-side

---

### ISSUE 1.2 — FeatureGate (plan limits + flags)

**Description**
Create `FeatureGate` service reading `config/features.php` and returning decisions:

- canRunFullRules, canUseAiSummary, canExportPdf, canUsePresets, canRunBatch
- maxDailyQaRuns, maxFileSizeBytes

**Acceptance criteria**

- FeatureGate has unit tests
- Controllers and jobs use FeatureGate consistently

---

### ISSUE 1.3 — Credits ledger + CreditsService

**Description**
Create `credits_ledger` with:

- org_id
- amount (+/-)
- reason
- meta_json
- idempotency_key (unique per org)
  Implement CreditsService:
- balance()
- debit() with idempotency
- credit()

**Acceptance criteria**

- Debit is idempotent under retries
- Balance matches ledger sum

---

### ISSUE 1.4 — Stripe subscription skeleton + webhooks

**Description**
Implement Stripe checkout + billing portal + webhook receiver.
Map Stripe subscription status to `subscriptions` table and org plan.

**Acceptance criteria**

- Subscribing updates org plan
- Canceling updates org plan
- Webhooks are verified

---

## Epic 2 — Upload + QA Run Creation

### ISSUE 2.1 — File upload UI (drag/drop)

**Description**
Create upload page with drag/drop, progress, and file type validation.

**Acceptance criteria**

- Upload works and shows progress
- Redirects to QA run page

---

### ISSUE 2.2 — Secure ingest pipeline

**Description**
Implement backend upload:

- extension allowlist
- basic signature check when possible
- checksum calculation
- store to filesystem disk
  Create design_files record.

**Acceptance criteria**

- Invalid file rejected
- Checksum stored
- File stored in correct path

---

### ISSUE 2.3 — CreateQaRunJob pipeline starter

**Description**
After upload, create qa_runs record and dispatch downstream jobs.
Broadcast realtime `qa.run.progress` events.

**Acceptance criteria**

- QA run page shows stages and progress
- Run status updates in DB

---

## Epic 3 — Parser/Renderer Service Integration

### ISSUE 3.1 — ParserClient (signed HTTP)

**Description**
Create a service class that calls parser microservice endpoints:

- parse
- render preview
- render density
- render jumps
  Use shared-secret signing and retries.

**Acceptance criteria**

- Service can parse a sample DST and returns metrics JSON
- Timeout and retry logic works

---

### ISSUE 3.2 — ParseEmbroideryFileJob

**Description**
Job calls ParserClient->parse, stores metrics to qa_metrics, updates status/progress, broadcasts progress.

**Acceptance criteria**

- qa_metrics row created with expected fields
- Progress updates appear in UI

---

### ISSUE 3.3 — RenderPreviewsJob + artifacts

**Description**
Generate preview/density/jumps images, store as qa_artifacts, broadcast artifact-ready events.

**Acceptance criteria**

- UI receives artifacts and renders them without refresh

---

## Epic 4 — Rule-based QA Engine

### ISSUE 4.1 — Presets + Rule registry

**Description**
Implement preset loading from `config/qa.php` and a Rule registry.
Define `RuleInterface` and Finding DTO.

**Acceptance criteria**

- Adding a new rule is one new class + registry entry
- Findings stored consistently with rule_key

---

### ISSUE 4.2 — Implement v1 rules

**Description**
Implement rules:

- hoop_limit_exceeded
- excessive_jump_count
- longest_jump_too_large
- too_many_color_changes
- min_stitch_length_too_short
- density_hotspots
- tiny_text_risk

**Acceptance criteria**

- Findings appear and are correct for sample inputs
- Severity thresholds follow preset config

---

### ISSUE 4.3 — Scoring + risk level

**Description**
Compute 0–100 score from findings using weights.
Map score to risk level low/medium/high.

**Acceptance criteria**

- Score is stable and deterministic
- Appears in UI and `qa.run.completed` event

---

## Epic 5 — LLM Provider Switching + AI Summary

### ISSUE 5.1 — LLM provider abstraction + router

**Description**
Implement:

- LlmClientInterface
- Provider drivers (OpenAI/Gemini/Anthropic)
- LlmRouter fallback chain

**Acceptance criteria**

- Router selects provider based on org preference
- Fallback works when provider fails

---

### ISSUE 5.2 — Encrypted API keys (per org) + test endpoint

**Description**
Create api_keys table and UI to save/test keys for each provider.

**Acceptance criteria**

- Keys encrypted at rest
- Test endpoint verifies key works

---

### ISSUE 5.3 — GenerateAiSummaryJob (paywalled)

**Description**
Generate AI summary strictly from structured metrics/findings.
Enforce FeatureGate or credits debit.
Validate strict JSON schema before saving.

**Acceptance criteria**

- AI summary appears on run page
- If AI fails, fallback to rules-only summary

---

## Epic 6 — Premium PDF Export

### ISSUE 6.1 — Premium PDF template

**Description**
Create premium report template (cover, score, top actions, findings, embedded images, operator checklist).

**Acceptance criteria**

- PDF looks polished and consistent
- Includes preview + heatmaps

---

### ISSUE 6.2 — GeneratePdfReportJob (paywalled)

**Description**
Job generates PDF artifact, stores it, and broadcasts completion.

**Acceptance criteria**

- Download button unlocks live
- Paywall enforced server-side

---

## Epic 7 — History, Sharing, Analytics

### ISSUE 7.1 — Projects + history + search filters

**Description**
Projects list, project details, run history with filters and indexing.

**Acceptance criteria**

- Filters work fast on MySQL
- User can find past runs easily

---

### ISSUE 7.2 — Shareable report links (tokenized)

**Description**
Create public read-only view via token with optional expiry.

**Acceptance criteria**

- Share link works without login
- Does not expose private data beyond allowed scope

---

### ISSUE 7.3 — QA feedback loop + insights

**Description**
Add “stitched ok / failed” buttons and collect machine/fabric fields.
Create insights dashboard for top failing rules.

**Acceptance criteria**

- Feedback stored
- Dashboard shows aggregated counts

---

## Epic 8 — Batch Personalization Add-on

### ISSUE 8.1 — Template builder v1

**Description**
Create templates (base design + placeholder_config_json + preset).
UI lets user define placeholder area and style.

**Acceptance criteria**

- Template saved and can generate a preview

---

### ISSUE 8.2 — CSV import + column mapping

**Description**
CSV upload, mapping UI, validation, create batch_runs/items.

**Acceptance criteria**

- Invalid CSV rows flagged clearly
- User can preview mapped data

---

### ISSUE 8.3 — ProcessBatchRunJob + realtime per-item events

**Description**
Queue job to generate proof per row and broadcast batch progress and item completion.

**Acceptance criteria**

- UI shows live progress and item list updates

---

### ISSUE 8.4 — Approvals portal (public)

**Description**
Tokenized approval page per batch item with approve/revision note.

**Acceptance criteria**

- Approval updates reflect in owner UI live

---

### ISSUE 8.5 — Batch export zip (paywalled + credits)

**Description**
Generate zip pack of proofs + job sheet.
Deduct credits per row and pause if insufficient.

**Acceptance criteria**

- Zip is downloadable and correct
- Credits handled safely with idempotency

---

## Epic 9 — Admin & Reliability

### ISSUE 9.1 — Admin panel (internal)

**Description**
Admin-only pages to manage:

- plan limits
- provider defaults
- failures and job metrics

**Acceptance criteria**

- Admin can toggle providers/limits without deploy

---

### ISSUE 9.2 — Support IDs + friendly failures

**Description**
All failures store support_id and error_code and broadcast failure events.

**Acceptance criteria**

- UI shows support ID
- Logs contain full error details

---

### ISSUE 9.3 — Rate limiting + abuse protections

**Description**
Throttle upload and expensive endpoints.
Use signed URLs/authorized routes for artifacts.

**Acceptance criteria**

- Basic abuse scenarios are blocked
- No unauthorized artifact download possible
