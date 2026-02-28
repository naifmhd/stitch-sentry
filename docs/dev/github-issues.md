# GitHub Issues Backlog (Full Descriptions + Acceptance Criteria)

This is the “single source of truth” issue list for StitchSentry.
Create one GitHub issue per section. Use the IDs (e.g., ISSUE 2.2) in branch names and commits.

Conventions:

- Laravel 12 + Inertia v2 + Vue 3 TS + Tailwind
- MySQL only (add indexes)
- Redis queues + Horizon
- Realtime via Laravel Reverb (payloads must match `docs/dev/realtime-events.md`)
- QA rule keys must match `docs/dev/qa-rules.md`
- Paywall enforced server-side via FeatureGate (controllers **and** jobs)
- Tests with Pest (Feature tests preferred)
- Run Pint on dirty PHP files: `vendor/bin/pint --dirty --format agent`

---

## Epic 0 — Foundation

## ISSUE 0.1 — Initialize Laravel + Inertia + Vue3 TS + Tailwind

**Goal**
Ensure the repo has a clean, premium-looking Inertia app shell to build on.

**Scope**

- Confirm Inertia v2 setup and pages directory (match existing convention).
- Ensure TypeScript configured and working (tsconfig, paths/aliases as used in repo).
- Create an authenticated app layout with:
    - Header (app name, user menu placeholder)
    - Sidebar navigation (Dashboard, Upload, Projects, Billing, Settings placeholders)
    - Content area with consistent spacing/typography (premium feel)
- Add Dashboard route + page rendered via Inertia after login.
- Ensure guest users are redirected to login for dashboard.

**Acceptance criteria**

- `GET /dashboard` redirects guests, loads for authed users.
- UI shell renders correctly on desktop and mobile.
- Pest test verifies auth redirect and Inertia component render.

---

## ISSUE 0.2 — MySQL baseline migrations/models

**Goal**
Lay down stable MySQL schema foundation for orgs/projects/files.

**Scope**

- Add migrations + models + factories for:
    - `organizations` (stub: name, owner_id optional)
    - `projects` (stub: organization_id, name)
    - `design_files` (stub: organization_id, user_id, project_id nullable, original_name, ext, size_bytes, checksum, storage_path, status)
- Add indexes:
    - foreign keys
    - created_at
    - checksum (unique or indexed depending on plan)

**Acceptance criteria**

- `php artisan migrate:fresh --seed` works.
- Factories exist and can create records.
- Basic tests assert relationships and creation succeed.

---

## ISSUE 0.3 — Redis queues + Horizon

**Goal**
Background jobs working reliably with named queues.

**Scope**

- Configure Redis queue connection.
- Configure Horizon and ensure it monitors queues we’ll use:
    - `ingest`, `parse`, `render`, `qa`, `ai`, `pdf`, `export`
- Create a dev job `PingQueueJob` (ShouldQueue) that logs a line and runs on `ingest`.
- Add a dev route/controller endpoint to dispatch the job and return JSON success.

**Acceptance criteria**

- Horizon runs locally and processes the job.
- Pest test uses `Queue::fake()` and asserts job pushed **on** `ingest`.

---

## ISSUE 0.4 — Laravel Reverb + Echo client

**Goal**
Realtime plumbing in place for live progress UI.

**Scope**

- Configure Reverb broadcasting and Vue Echo client.
- Implement private channel auth for `private-org.{orgId}`.
- Create `resources/js/Pages/Dev/ReverbTest.vue`:
    - subscribes to `private-org.{orgId}`
    - logs/prints events live
- Create backend route/controller action that broadcasts a sample `qa.run.progress` payload matching `docs/dev/realtime-events.md`.

**Acceptance criteria**

- Reverb runs locally and browser receives events without refresh.
- Broadcast payload fields match schema exactly.
- Pest test verifies endpoint requires auth and returns 200 for authed users.

---

## Epic 1 — Organizations, Plans, Billing, Credits

## ISSUE 1.1 — Organizations + roles + org switcher

**Goal**
Multi-tenant foundation with role-based access.

**Scope**

- Create tables:
    - `organizations`
    - `organization_user` pivot: organization_id, user_id, role (owner/admin/member), timestamps
- Add middleware to set “current org” (session or user setting).
- Add policy helpers to ensure user belongs to org.
- UI:
    - org switcher in header
    - org settings page (members list, invite placeholder)
- Seed/dev factory for org membership.

**Acceptance criteria**

- User can switch org context.
- Unauthorized access to other org resources blocked.
- Pest tests cover org switching + access control.

---

## ISSUE 1.2 — FeatureGate (plan limits + flags)

**Goal**
Central server-side feature gating used everywhere.

**Scope**

- Implement `FeatureGate` service reading `config/features.php`.
- Methods:
    - `canRunFullRules`, `canUseAiSummary`, `canExportPdf`, `canUsePresets`, `canRunBatch`
    - `maxDailyQaRuns`, `maxFileSizeBytes`
- Integrate FeatureGate into:
    - upload endpoint (file size, daily limits)
    - expensive actions (AI/PDF/batch) — even if stubbed

**Acceptance criteria**

- Unit tests for FeatureGate logic.
- At least one controller enforces limits server-side.

---

## ISSUE 1.3 — Credits ledger + CreditsService

**Goal**
Usage-based billing foundation with idempotent debits.

**Scope**

- Table `credits_ledger`:
    - org_id, amount (signed int), reason, meta_json, idempotency_key, created_at
    - unique index (org_id, idempotency_key)
- CreditsService:
    - `balance(org)`
    - `debit(org, amount, reason, meta, idempotencyKey)`
    - `credit(org, amount, reason, meta, idempotencyKey)`
- UI:
    - credits badge in header
    - billing/credits page stub
- Add tests for idempotency (same key doesn’t double-debit).

**Acceptance criteria**

- Debits are safe under retries.
- Balance equals ledger sum.
- Tests pass.

---

## ISSUE 1.4 — Stripe subscription skeleton + webhooks

**Goal**
Subscription plan tracking for FeatureGate.

**Scope**

- Create `subscriptions` table:
    - org_id, stripe_customer_id, stripe_subscription_id, status, plan_slug, period_end_at
- Implement:
    - start checkout (stub price id)
    - billing portal link
    - webhook receiver (signature verification)
- On webhook updates, update `subscriptions.plan_slug` used by FeatureGate.

**Acceptance criteria**

- Webhook endpoint verifies signature (unit test).
- Billing page shows plan status from DB.
- No long work in webhook handler (queue if needed).

---

## Epic 2 — Upload + QA Run Creation

## ISSUE 2.1 — File upload UI (drag/drop)

**Goal**
Premium upload UX to start QA flow.

**Scope**

- Inertia page with drag/drop + progress.
- Client-side allowlist: DST only initially.
- Show helpful empty states and “what happens next” explanation.
- On success redirect to QA Run live page.

**Acceptance criteria**

- Upload page works end-to-end against backend endpoint.
- UI looks premium and responsive.

---

## ISSUE 2.2 — Secure ingest pipeline

**Goal**
Secure file upload and storage.

**Scope**

- Backend endpoint:
    - auth required
    - validate extension allowlist
    - enforce file size using FeatureGate
    - compute checksum (sha256)
    - store file via Laravel filesystem
    - create `design_files` record with status
- Add route throttling.
- Return redirect to run creation.

**Acceptance criteria**

- Invalid extension rejected.
- File stored and DB row created.
- Pest tests for auth, validation, and DB insert.

---

## ISSUE 2.3 — CreateQaRunJob pipeline starter

**Goal**
QA run orchestration + realtime progress page.

**Scope**

- Tables:
    - `qa_runs`: design_file_id, organization_id, preset, status, progress, started_at, finished_at, score nullable, risk_level nullable
- Implement:
    - After upload create `qa_runs` row
    - Dispatch `CreateQaRunJob` to `ingest`
- Realtime:
    - broadcast `qa.run.progress` events per schema
- UI QA run live page:
    - fetch initial state via HTTP
    - subscribe to Reverb and update progress + stage timeline live

**Acceptance criteria**

- QA run created and job dispatched.
- UI updates live without refresh.
- Pest test asserts run created and job pushed.

---

## Epic 3 — Parser/Renderer Service Integration

## ISSUE 3.1 — ParserClient (signed HTTP)

**Goal**
Clean integration layer to Python parser microservice.

**Scope**

- Create `config/parser.php`:
    - base URL, secret, timeout, mock mode flag
- ParserClient methods:
    - `parse()`, `renderPreview()`, `renderDensity()`, `renderJumps()`
- Signed request:
    - timestamp + HMAC secret in headers
- Tests for signature generation and required headers.

**Acceptance criteria**

- Client composes correct request headers.
- Config documented in `.env.example`.

---

## ISSUE 3.2 — ParseEmbroideryFileJob

**Goal**
Populate metrics in DB from parser output.

**Scope**

- Table `qa_metrics` (1:1 with qa_run):
    - width_mm, height_mm, stitch_count, color_changes, jump_count, longest_jump_mm,
      min_stitch_length_mm, max_stitch_length_mm, avg_stitch_length_mm
- Job:
    - loads file path
    - calls ParserClient->parse (or mock mode)
    - stores qa_metrics
    - updates qa_run progress + broadcasts `qa.run.progress`

**Acceptance criteria**

- qa_metrics stored for run.
- UI shows metrics when available (basic).
- Test covers mock mode path.

---

## ISSUE 3.3 — RenderPreviewsJob + artifacts

**Goal**
Generate preview/density/jumps images and notify UI realtime.

**Scope**

- Table `qa_artifacts`:
    - qa_run_id, kind (preview/density/jumps/pdf), storage_path, meta_json
- Job:
    - call render endpoints
    - store PNGs
    - create qa_artifacts rows
    - broadcast `qa.run.artifact.ready` per schema
- UI:
    - show images as soon as events arrive

**Acceptance criteria**

- Artifacts stored and visible without refresh.
- Tests assert qa_artifacts created.

---

## Epic 4 — Rule-based QA Engine

## ISSUE 4.1 — Presets + Rule registry

**Goal**
Extensible rules framework with stable rule keys.

**Scope**

- Create RuleInterface + Finding DTO.
- RuleRegistry selects rule classes per preset.
- Load thresholds from `config/qa.php`.
- Ensure rule_key strings match `docs/dev/qa-rules.md`.

**Acceptance criteria**

- Adding a new rule requires only a new class + registry entry.
- Unit tests verify one rule outputs expected severity.

---

## ISSUE 4.2 — Implement v1 rules

**Goal**
Production-ready v1 warnings/fails.

**Scope**
Implement rules:

- `hoop_limit_exceeded`
- `excessive_jump_count`
- `longest_jump_too_large`
- `too_many_color_changes`
- `min_stitch_length_too_short`
- `density_hotspots` (use metrics proxy first; later use heatmap)
- `tiny_text_risk` heuristic

Each finding includes:

- severity, title, message, recommendation, evidence_json

**Acceptance criteria**

- Findings saved to `qa_findings`.
- Tests for each rule (input metrics → expected severity).
- UI groups findings by severity.

---

## ISSUE 4.3 — Scoring + risk level

**Goal**
User-friendly score that powers UI and “value”.

**Scope**

- Scoring service:
    - uses weights from `config/qa.php`
    - score 0–100
    - risk_level mapping (low/medium/high)
- Store on qa_runs and broadcast `qa.run.completed`.

**Acceptance criteria**

- Score deterministic for same inputs.
- UI shows score gauge and risk label.
- Tests cover scoring logic.

---

## Epic 5 — LLM Provider Switching + AI Summary

## ISSUE 5.1 — LLM provider abstraction + router

**Goal**
Swap providers without rewriting business logic.

**Scope**

- LlmClientInterface
- Providers:
    - OpenAI, Gemini, Anthropic (config-driven)
- Router:
    - org preference
    - fallback chain from config
    - returns rules-only summary if all fail
- Tests for routing + fallback.

**Acceptance criteria**

- No code changes needed to switch provider (config + DB preference).
- Router fallback works in tests.

---

## ISSUE 5.2 — Encrypted API keys (per org) + test endpoint

**Goal**
Org can use their own API key.

**Scope**

- Table `api_keys`:
    - owner_type, owner_id, provider, encrypted_key
- UI in org settings:
    - add/update key
    - test key endpoint
- Ensure keys encrypted at rest (Laravel encrypted casts).

**Acceptance criteria**

- Unauthorized users cannot view keys.
- Test endpoint returns safe success/failure.
- Tests verify encryption and authorization.

---

## ISSUE 5.3 — GenerateAiSummaryJob (paywalled)

**Goal**
Premium “AI + rules” summary.

**Scope**

- Job:
    - re-check FeatureGate (and/or debit credits idempotently)
    - send ONLY structured metrics + findings
    - require strict JSON output; validate schema before saving
    - store `ai_summary_json` on qa_runs
    - broadcast progress stage updates
- Fallback to rules-only summary.

**Acceptance criteria**

- Free plan blocked (server-side).
- Schema validation prevents junk.
- Tests cover paywall and schema validation.

---

## Epic 6 — Premium PDF Export

## ISSUE 6.1 — Premium PDF template

**Goal**
Deliver a report that feels worth paying for.

**Scope**

- PDF layout:
    - cover page
    - score + risk
    - top actions
    - findings table
    - embed preview + heatmaps
    - operator checklist
- Use consistent styling and spacing.

**Acceptance criteria**

- PDF generated successfully with embedded images.
- Looks professional.

---

## ISSUE 6.2 — GeneratePdfReportJob (paywalled)

**Goal**
Async PDF generation + live “ready” indicator.

**Scope**

- Job:
    - re-check FeatureGate / debit credits idempotently
    - generate PDF and store
    - create qa_artifacts(kind=pdf)
    - broadcast `qa.run.artifact.ready` with kind `pdf`

**Acceptance criteria**

- Free users blocked server-side.
- UI shows download button as soon as event arrives.
- Tests verify artifact created and paywall enforced.

---

## Epic 7 — History, Sharing, Analytics

## ISSUE 7.1 — Projects + history + search filters

**Goal**
Make the tool usable daily.

**Scope**

- Projects index + detail
- QA run history with filters:
    - preset, severity, date range, filename search
- Add proper MySQL indexes.
- Pagination.

**Acceptance criteria**

- Filters fast on realistic dataset.
- Tests verify access control and filters.

---

## ISSUE 7.2 — Shareable report links (tokenized)

**Goal**
Share reports with clients safely.

**Scope**

- Token share links with optional expiry.
- Public read-only page.
- Respect paywall: optionally hide AI section for free.

**Acceptance criteria**

- Token cannot access other org data.
- Expired token rejected.
- Tests for token access + expiry.

---

## ISSUE 7.3 — QA feedback loop + insights

**Goal**
Collect outcome data and show value.

**Scope**

- UI feedback:
    - stitched ok / thread breaks / puckered / trims messy
    - machine brand/model, fabric, notes
- Store to `qa_feedback`.
- Insights dashboard:
    - top failing rules
    - failure rate by preset
    - trends

**Acceptance criteria**

- Feedback stored and visible in dashboard.
- Queries indexed and fast.
- Tests cover feedback creation and org isolation.

---

## Epic 8 — Batch Personalization Add-on

## ISSUE 8.1 — Template builder v1

**Goal**
Define template used for CSV proof generation.

**Scope**

- `templates` table:
    - org_id, name, base_design_file_id, placeholder_config_json, preset
- UI to create template:
    - choose base design
    - set placeholder box + style preset
- v1: proofs only (no stitch-file editing).

**Acceptance criteria**

- Template saved and viewable.
- Tests cover create/update.

---

## ISSUE 8.2 — CSV import + column mapping

**Goal**
Reliable CSV intake.

**Scope**

- Tables:
    - `batch_runs` (template_id, status, progress, total_rows)
    - `batch_items` (batch_run_id, row_index, input_json, status, proof_path, approval_status)
- UI:
    - upload CSV
    - map columns to fields
    - preview rows and show validation errors

**Acceptance criteria**

- Invalid rows surfaced clearly.
- Tests cover validation and batch creation.

---

## ISSUE 8.3 — ProcessBatchRunJob + realtime per-item events

**Goal**
Automated proof generation at scale.

**Scope**

- Job:
    - iterate items
    - generate proof image
    - create approval token
    - broadcast `batch.run.progress` and `batch.item.completed`
- Idempotent/resumable: skip already completed items.

**Acceptance criteria**

- Job can be restarted without duplicating work.
- UI updates per item live.
- Tests cover resumption behavior.

---

## ISSUE 8.4 — Approvals portal (public)

**Goal**
Client approval workflow.

**Scope**

- Tokenized public page:
    - view proof
    - approve or request revision with notes
- Owner UI updates live via Reverb.
- Prevent token enumeration.

**Acceptance criteria**

- Token access secure.
- Approval state updates reflected.
- Tests for token access and state transitions.

---

## ISSUE 8.5 — Batch export zip (paywalled + credits)

**Goal**
Deliver production pack.

**Scope**

- Job:
    - re-check FeatureGate / debit credits
    - generate zip of proofs + job sheet CSV/PDF
    - pause batch if credits insufficient
- Secure download route.

**Acceptance criteria**

- Zip contains correct files.
- Credits deducted idempotently.
- Tests cover paywall/credits enforcement.

---

## Epic 9 — Admin & Reliability

## ISSUE 9.1 — Admin panel (internal)

**Goal**
Operate the service.

**Scope**

- Admin-only pages:
    - plan limits
    - provider toggles/defaults
    - job failures list + retry controls
- Policies and route protection.

**Acceptance criteria**

- Non-admin blocked.
- Admin can update settings.
- Tests cover access control.

---

## ISSUE 9.2 — Support IDs + friendly failures

**Goal**
Production-grade failure UX.

**Scope**

- Standardize failures in jobs:
    - generate support_id
    - store error_code/support_id on run/batch
    - broadcast `qa.run.failed` / `batch.run.failed`
- UI shows friendly message + support id.
- Add structured logging.

**Acceptance criteria**

- Failures persist support_id.
- Events match schema.
- Tests cover failure persistence and broadcast.

---

## ISSUE 9.3 — Rate limiting + abuse protections

**Goal**
Prevent abuse and data leaks.

**Scope**

- Throttle uploads and expensive endpoints.
- Secure artifact downloads:
    - signed temporary URLs or authorized download routes
- Ensure share links don’t leak org data.

**Acceptance criteria**

- Basic abuse scenarios blocked.
- Unauthorized artifact download prevented.
- Tests cover throttling/auth routes.
