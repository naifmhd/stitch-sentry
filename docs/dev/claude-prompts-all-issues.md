# Claude Code Prompt Set — All GitHub Issues

Use these prompts **one issue at a time** with Claude Code.
Before running, ensure `CLAUDE.md` exists at repo root with your constraints.

General instruction for every run:

- Follow `docs/dev/conventions.md`
- Match realtime payloads exactly per `docs/dev/realtime-events.md`
- Use rule keys exactly per `docs/dev/qa-rules.md`
- MySQL only; add indexes
- Never block HTTP for long tasks; use queued Jobs + Horizon
- Enforce paywall server-side via FeatureGate in controllers **and** jobs
- After implementation run:
    - `php artisan migrate:fresh --seed`
    - `php artisan test` (or add at least a smoke test)
    - `npm run build` (or if not available, `npm run dev` sanity)

---

## Epic 0 — Foundation

### ISSUE 0.1 — Initialize Laravel + Inertia + Vue3 TS + Tailwind

**Prompt**
Implement ISSUE 0.1 from `docs/dev/github-issues.md`.
Set up Laravel + Inertia + Vue 3 + TypeScript + Tailwind with auth scaffolding.
Create a premium base layout shell (header + sidebar + content) and a `Dashboard` page after login.
Keep controllers thin; domain logic in `app/Domain`.
Run the commands listed above and report outputs.

### ISSUE 0.2 — MySQL + baseline migrations/models

**Prompt**
Implement ISSUE 0.2 from `docs/dev/github-issues.md`.
Configure MySQL and create baseline migrations/models/factories for: organizations (stub), projects (stub), design_files (stub).
Add appropriate indexes. Ensure `migrate:fresh --seed` works.
Add minimal tests to confirm models and relationships.

### ISSUE 0.3 — Redis queues + Horizon

**Prompt**
Implement ISSUE 0.3 from `docs/dev/github-issues.md`.
Configure Redis queues and Horizon. Add a test job and a route/button in a Dev page to dispatch it.
Ensure the job uses a named queue (`ingest`).
Verify processing via Horizon and log output.

### ISSUE 0.4 — Laravel Reverb + Echo client

**Prompt**
Implement ISSUE 0.4 from `docs/dev/github-issues.md`.
Configure Laravel Reverb broadcasting and Vue Echo client.
Create `resources/js/Pages/Dev/ReverbTest.vue` subscribing to `private-org.{orgId}` and printing payloads.
Add a backend route/controller to broadcast a sample `qa.run.progress` payload matching `docs/dev/realtime-events.md` exactly.
Verify locally with `php artisan reverb:start`.

---

## Epic 1 — Organizations, Plans, Billing, Credits

### ISSUE 1.1 — Organizations + roles + org switcher

**Prompt**
Implement ISSUE 1.1 from `docs/dev/github-issues.md`.
Create organizations + organization_user pivot with roles (owner/admin/member).
Add org switcher UI in app shell and org settings page.
Add middleware to set “current org” in session and enforce access policies.
Add tests for org switching and authorization.

### ISSUE 1.2 — FeatureGate (plan limits + flags)

**Prompt**
Implement ISSUE 1.2 from `docs/dev/github-issues.md`.
Create `FeatureGate` reading `config/features.php` and implementing:
canRunFullRules, canUseAiSummary, canExportPdf, canUsePresets, canRunBatch, maxDailyQaRuns, maxFileSizeBytes.
Enforce gates server-side in relevant controllers and (where applicable) in jobs.
Add unit tests for FeatureGate.

### ISSUE 1.3 — Credits ledger + CreditsService

**Prompt**
Implement ISSUE 1.3 from `docs/dev/github-issues.md`.
Create `credits_ledger` with idempotency_key unique per org.
Implement CreditsService: balance, debit (idempotent), credit.
Add UI badge for credit balance in header and a Billing/Credits page stub.
Add tests verifying idempotent debit.

### ISSUE 1.4 — Stripe subscription skeleton + webhooks

**Prompt**
Implement ISSUE 1.4 from `docs/dev/github-issues.md`.
Implement Stripe checkout + billing portal link + webhook receiver with signature verification.
Persist stripe_customer_id, stripe_subscription_id, status in `subscriptions`.
Map subscription to plan slug used by FeatureGate.
Add Billing page showing current plan/status. Include local dev instructions.

---

## Epic 2 — Upload + QA Run Creation

### ISSUE 2.1 — File upload UI (drag/drop)

**Prompt**
Implement ISSUE 2.1 from `docs/dev/github-issues.md`.
Create an upload page with drag/drop, progress, and client-side validation (DST only for now).
Premium UI: cards, clear typography, helpful empty states.
On success redirect to QA run live page.

### ISSUE 2.2 — Secure ingest pipeline

**Prompt**
Implement ISSUE 2.2 from `docs/dev/github-issues.md`.
Backend upload endpoint:

- validate allowlist (DST for now)
- enforce max file size via FeatureGate
- compute checksum
- store via Laravel filesystem disk
- create `design_files` row
  Add throttling and meaningful errors.
  Return design_file id and redirect target.

### ISSUE 2.3 — CreateQaRunJob pipeline starter

**Prompt**
Implement ISSUE 2.3 from `docs/dev/github-issues.md`.
Create `qa_runs` schema and after upload create a run, dispatch `CreateQaRunJob` to `ingest`.
Create a QA run live page that fetches current state via HTTP and listens to Reverb events on `private-org.{orgId}`.
Broadcast `qa.run.progress` exactly per `docs/dev/realtime-events.md`.
Include stage timeline + progress bar.

---

## Epic 3 — Parser/Renderer Service Integration

### ISSUE 3.1 — ParserClient (signed HTTP)

**Prompt**
Implement ISSUE 3.1 from `docs/dev/github-issues.md`.
Create ParserClient (Laravel Http) with signed requests (timestamp + HMAC using PARSER_SERVICE_SECRET).
Add endpoints: parse, renderPreview, renderDensity, renderJumps.
Add config (`config/parser.php`) and update `.env.example`.
Add tests for signature generation and request headers.

### ISSUE 3.2 — ParseEmbroideryFileJob

**Prompt**
Implement ISSUE 3.2 from `docs/dev/github-issues.md`.
Implement ParseEmbroideryFileJob:

- loads the uploaded file
- calls ParserClient->parse
- stores results into `qa_metrics`
- updates `qa_runs` status/progress
- broadcasts `qa.run.progress`
  Add a parser-mock mode for local dev (config flag) that returns deterministic fake metrics for DST files.

### ISSUE 3.3 — RenderPreviewsJob + artifacts

**Prompt**
Implement ISSUE 3.3 from `docs/dev/github-issues.md`.
Implement RenderPreviewsJob:

- calls ParserClient render endpoints
- stores PNG artifacts (preview/density/jumps) via filesystem
- creates `qa_artifacts` records
- broadcasts `qa.run.artifact.ready` per events doc
  Update QA run page to display artifacts as soon as they arrive.

---

## Epic 4 — Rule-based QA Engine

### ISSUE 4.1 — Presets + Rule registry

**Prompt**
Implement ISSUE 4.1 from `docs/dev/github-issues.md`.
Create RuleInterface + Finding DTO and RuleRegistry mapping preset -> rules list.
Load thresholds from `config/qa.php`.
Ensure finding.rule_key matches `docs/dev/qa-rules.md` exactly.
Write tests: given metrics/preset, rule outputs expected severity.

### ISSUE 4.2 — Implement v1 rules

**Prompt**
Implement ISSUE 4.2 from `docs/dev/github-issues.md`.
Implement rule classes for:

- hoop_limit_exceeded
- excessive_jump_count
- longest_jump_too_large
- too_many_color_changes
- min_stitch_length_too_short
- density_hotspots
- tiny_text_risk
  Each finding includes: severity, title, message, recommendation, evidence_json.
  Ensure thresholds are read from config and vary per preset.

### ISSUE 4.3 — Scoring + risk level

**Prompt**
Implement ISSUE 4.3 from `docs/dev/github-issues.md`.
Create scoring service using weights from `config/qa.php`.
Store score + risk level on `qa_runs` and include in `qa.run.completed` event.
Update UI to show a score gauge and risk label.

---

## Epic 5 — LLM Provider Switching + AI Summary

### ISSUE 5.1 — LLM provider abstraction + router

**Prompt**
Implement ISSUE 5.1 from `docs/dev/github-issues.md`.
Create LlmClientInterface + provider drivers (OpenAI/Gemini/Anthropic) and LlmRouter fallback chain per `config/llm.php`.
Do not hardcode models; read from config.
Add tests for router selection and fallback.

### ISSUE 5.2 — Encrypted API keys (per org) + test endpoint

**Prompt**
Implement ISSUE 5.2 from `docs/dev/github-issues.md`.
Create `api_keys` table storing encrypted API keys per org/provider.
Create UI in Org Settings to add/update/test keys.
Testing endpoint should perform a minimal call and return success/failure safely.

### ISSUE 5.3 — GenerateAiSummaryJob (paywalled)

**Prompt**
Implement ISSUE 5.3 from `docs/dev/github-issues.md`.
GenerateAiSummaryJob:

- re-check FeatureGate / debit credits with idempotency
- send ONLY structured metrics + findings to LLM
- enforce strict JSON schema and validate before saving
- store ai_summary_json on qa_runs
- broadcast progress stages
  Fallback to rules-only summary if all providers fail.

---

## Epic 6 — Premium PDF Export

### ISSUE 6.1 — Premium PDF template

**Prompt**
Implement ISSUE 6.1 from `docs/dev/github-issues.md`.
Create a premium PDF report template:
cover page, score, top actions, findings table, embedded preview+heatmaps, operator checklist.
Ensure it looks like a paid SaaS deliverable (spacing, typography, sectioning).

### ISSUE 6.2 — GeneratePdfReportJob (paywalled)

**Prompt**
Implement ISSUE 6.2 from `docs/dev/github-issues.md`.
GeneratePdfReportJob:

- re-check FeatureGate / debit credits with idempotency
- generate/store PDF artifact
- create qa_artifacts row
- broadcast `qa.run.artifact.ready` with kind `pdf`
  Update UI to show Download PDF when ready.

---

## Epic 7 — History, Sharing, Analytics

### ISSUE 7.1 — Projects + history + search filters

**Prompt**
Implement ISSUE 7.1 from `docs/dev/github-issues.md`.
Build Projects list/detail and QA run history pages with filters (preset/severity/date).
Add MySQL indexes to keep queries fast.
Add server-side pagination and search by filename/checksum.

### ISSUE 7.2 — Shareable report links (tokenized)

**Prompt**
Implement ISSUE 7.2 from `docs/dev/github-issues.md`.
Create tokenized share links with optional expiry.
Public view must be read-only and only show permitted sections (respect paywall).
Add tests ensuring tokens cannot access other org data.

### ISSUE 7.3 — QA feedback loop + insights

**Prompt**
Implement ISSUE 7.3 from `docs/dev/github-issues.md`.
Add feedback UI (stitched ok / thread breaks / puckered / trims messy) with machine/fabric fields.
Store to qa_feedback. Build an insights dashboard: top failing rules, failure rates by preset.
Ensure the dashboard queries are optimized.

---

## Epic 8 — Batch Personalization Add-on

### ISSUE 8.1 — Template builder v1

**Prompt**
Implement ISSUE 8.1 from `docs/dev/github-issues.md`.
Create templates table with placeholder_config_json and base_design_file_id.
UI: choose base design, set placeholder area and style preset.
Keep v1 focused on proof generation (not stitch-file edits).

### ISSUE 8.2 — CSV import + column mapping

**Prompt**
Implement ISSUE 8.2 from `docs/dev/github-issues.md`.
CSV upload + column mapping UI. Validate required columns.
Create batch_runs and batch_items.
Show preview and validation errors per row.

### ISSUE 8.3 — ProcessBatchRunJob + realtime per-item events

**Prompt**
Implement ISSUE 8.3 from `docs/dev/github-issues.md`.
ProcessBatchRunJob:

- iterate rows
- generate proof image per item
- create approval token
- broadcast `batch.run.progress` and `batch.item.completed` exactly per events doc
  Ensure the job is resumable and does not reprocess completed items.

### ISSUE 8.4 — Approvals portal (public)

**Prompt**
Implement ISSUE 8.4 from `docs/dev/github-issues.md`.
Public approval page per token:

- view proof
- approve or request revision with notes
  Update owner UI live via Reverb. Prevent token enumeration.

### ISSUE 8.5 — Batch export zip (paywalled + credits)

**Prompt**
Implement ISSUE 8.5 from `docs/dev/github-issues.md`.
GenerateBatchExportZipJob:

- re-check FeatureGate / debit credits
- generate zip of proofs + job sheet
- pause batch if credits insufficient
  Broadcast completion events and expose download route securely.

---

## Epic 9 — Admin & Reliability

### ISSUE 9.1 — Admin panel (internal)

**Prompt**
Implement ISSUE 9.1 from `docs/dev/github-issues.md`.
Admin-only pages:

- manage plan limits
- toggle LLM providers and defaults
- view job failures and retry controls
  Use policies and protect routes.

### ISSUE 9.2 — Support IDs + friendly failures

**Prompt**
Implement ISSUE 9.2 from `docs/dev/github-issues.md`.
Standardize failure handling:

- generate support_id
- store error_code/support_id on run/batch
- broadcast `qa.run.failed` / `batch.run.failed`
- UI displays user-friendly message + support_id
  Add tests for failure persistence.

### ISSUE 9.3 — Rate limiting + abuse protections

**Prompt**
Implement ISSUE 9.3 from `docs/dev/github-issues.md`.
Add throttling for upload and expensive endpoints.
Secure artifact downloads with signed URLs or auth routes.
Add tests for authorization and basic abuse scenarios.
