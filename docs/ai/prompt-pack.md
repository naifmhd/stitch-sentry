# StitchSentry – Codex/Copilot Prompt Pack

Use these prompts with Codex or Copilot Chat. Each prompt is self-contained and includes constraints.

## Global constraints (include in every prompt)

- Laravel 11/12, PHP 8.3+
- MySQL only
- Inertia + Vue 3 + TypeScript + Tailwind
- Redis queues + Horizon
- Realtime via Laravel Reverb
- Storage via Laravel filesystem (S3-compatible later)
- All long work via Jobs (never block HTTP)
- All paywall checks enforced server-side
- LLM calls go through provider router (OpenAI/Gemini/Anthropic)
- Provide complete files with file paths when editing

---

## Prompt 0 — Create conventions & docs skeleton

Create:

- `docs/dev/conventions.md`
- `docs/dev/realtime-events.md`
- `docs/dev/qa-rules.md`
  Include folder layout, event payload schema, and rule key registry.

---

## Epic 0 — Foundation

### Prompt 1 — Initialize Laravel + Inertia + Vue TS + Tailwind

Implement Inertia stack, Vue 3 + TS setup, Tailwind config, auth scaffolding, base layout.

Acceptance:

- `php artisan serve` and `npm run dev` work
- Auth pages render through Inertia

### Prompt 2 — Configure MySQL + Redis + Horizon

Add queue config + Horizon config.
Add a test job and route to dispatch it.

Acceptance:

- Job runs via Horizon and logs output

### Prompt 3 — Configure Laravel Reverb + Echo

Add broadcasting config, Reverb config, frontend Echo client, and a test event.

Acceptance:

- A test page receives realtime events without refresh

---

## Epic 1 — Orgs, Plans, Billing, Credits

### Prompt 4 — Organizations + roles

Create migrations/models:

- organizations
- organization_user (role: owner/admin/member)
  Add policies/middleware for org context.
  Add UI: org switcher + org settings page.

### Prompt 5 — Plans + feature gating

Implement:

- `Plan`, `Subscription` models (initial)
- `FeatureGate` service with methods:
    - canRunFullRules, canUseAiSummary, canExportPdf, canUsePresets, canRunBatch
    - maxDailyQaRuns, maxFileSizeBytes

Enforce gates server-side (controllers + jobs).

### Prompt 6 — Credits ledger

Implement:

- `credits_ledger` table with idempotency key
- `CreditsService` (balance/debit/credit)
  Add UI: credit balance + buy credits CTA.

### Prompt 7 — Stripe billing skeleton

Implement subscription checkout + webhook receiver.
Update org plan on webhook.
Add Billing page with plan status.

---

## Epic 2 — Upload + QA run pipeline

### Prompt 8 — File upload UI + backend

Implement:

- `design_files` table (checksum, storage_path, ext, size_bytes, status)
- Upload endpoint storing to filesystem disk
- Upload page (drag/drop) with progress
  Auto-create QA run after upload and redirect.

### Prompt 9 — QA runs + pipeline skeleton + realtime progress

Implement tables:

- qa_runs, qa_metrics, qa_findings, qa_artifacts
  Implement jobs:
- CreateQaRunJob
- broadcast progress at each stage
  Create run page that subscribes to events and renders progress bar.

---

## Epic 3 — Parser/Renderer integration

### Prompt 10 — ParserClient HTTP service

Create `ParserClient` using Laravel HTTP client:

- parse(file)
- renderPreview(file)
- renderDensity(file)
- renderJumps(file)
  Use signed requests (secret + timestamp).
  Handle timeouts and retries.

### Prompt 11 — ParseEmbroideryFileJob

Call ParserClient->parse and store metrics.
Broadcast progress.

### Prompt 12 — RenderPreviewsJob

Generate and store preview/density/jumps artifacts.
Broadcast artifact-ready events.
UI shows images as they arrive.

---

## Epic 4 — Rule-based QA engine

### Prompt 13 — Presets + rule registry

Implement:

- preset definitions reading from config/qa.php
- `RuleInterface` with `evaluate() : Finding[]`
- `RuleRegistry` mapping preset -> rules list

Each finding must include:

- rule_key, severity, title, message, recommendation, evidence_json

### Prompt 14 — Implement v1 rules

Implement:

- hoop limit exceeded
- excessive jump count
- longest jump too large
- too many color changes
- min stitch length too short
- density hotspot tiles too high
- tiny text risk heuristic

Store findings and compute score (0-100).

### Prompt 15 — RunRuleQaJob

Run the registry, store findings, update run status:

- completed
- completed_with_failures
  Broadcast progress.

---

## Epic 5 — LLM provider switching

### Prompt 16 — LLM abstraction + router

Create:

- LlmClientInterface
- OpenAiClient, GeminiClient, AnthropicClient
- LlmRouter (org preference + per-org keys + fallback chain)
  Add config `config/llm.php`.

### Prompt 17 — Encrypted API keys UI + storage

Create `api_keys` table with encrypted key.
UI: set/update/test provider keys per-org.
Test endpoint does minimal API call.

### Prompt 18 — GenerateAiSummaryJob (paywalled)

Use only metrics + findings.
Enforce FeatureGate and/or credits debit with idempotency key.
Store JSON summary on qa_runs.

---

## Epic 6 — Premium PDF export

### Prompt 19 — Premium PDF template

Generate a PDF report:

- cover page
- score gauge
- top actions
- findings table
- embedded preview + heatmaps
- operator checklist

### Prompt 20 — GeneratePdfReportJob (paywalled)

Generate PDF artifact, store, broadcast completion.
UI enables download button.

---

## Epic 7 — History, sharing, dashboard

### Prompt 21 — Projects + history + filters

Build pages:

- Projects index
- Project detail (files + runs)
- Run filters by preset/severity/date
  Optimize MySQL indexes.

### Prompt 22 — Share links

Tokenized, read-only share view.
Optionally hide AI section for free share.

### Prompt 23 — Feedback loop

Buttons: stitched ok / thread breaks / puckered / trims messy.
Store to qa_feedback.
Dashboard: top failing rules and trends.

---

## Epic 8 — Batch personalization add-on

### Prompt 24 — Template builder v1

Create templates with placeholder_config_json.
UI: pick base design + define placeholder box + style preset.

### Prompt 25 — CSV upload + mapping + validation

Create batch_runs, batch_items.
UI maps CSV columns and previews rows.

### Prompt 26 — ProcessBatchRunJob + realtime updates

Process each row:

- generate proof image
- create approval link
  Broadcast per-item completion.

### Prompt 27 — Approvals portal

Public approval link per batch item:

- approve/revision note
  Reflect status in owner UI via Reverb.

### Prompt 28 — Export pack zip (paywalled + credits)

Generate zip of proofs + job sheet.
Deduct credits per row.
Pause if credits insufficient.

---

## Epic 9 — Admin & reliability

### Prompt 29 — Admin panel (internal)

Manage plans/limits, default LLM providers, and view failures.

### Prompt 30 — Support IDs on failures

Every failed run/batch stores support_id and broadcasts failure event.
UI shows friendly message + support id.

### Prompt 31 — Rate limiting + abuse protection

Throttles for uploads and expensive operations.
Signed URLs for artifacts.
