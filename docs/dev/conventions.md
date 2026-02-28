# Development Conventions (StitchSentry)

## Goals

- Thin controllers; business logic in `app/Domain/*`
- No long-running work in HTTP requests: use queues
- Paywall enforced server-side (services/policies/jobs)
- Realtime UI from Reverb events
- LLM is pluggable (OpenAI / Gemini / Anthropic)

---

## Folder structure

### Backend

```
app/
  Domain/
    Qa/
      Actions/
      Rules/
      DTO/
      Services/
      Support/
    Batch/
      Actions/
      DTO/
      Services/
    Billing/
      Services/
      Support/
    Llm/
      Contracts/
      Providers/
      Services/
  Http/Controllers/
  Jobs/
  Events/
  Policies/
  Models/
```

### Frontend

```
resources/js/
  Pages/
  Components/
    ui/
    qa/
    batch/
  Composables/
  Types/
```

### Docs

```
docs/
  dev/
  ai/
```

---

## Naming

### Jobs

- `CreateQaRunJob`
- `ParseEmbroideryFileJob`
- `RenderPreviewsJob`
- `RunRuleQaJob`
- `GenerateAiSummaryJob`
- `GeneratePdfReportJob`
- `ProcessBatchRunJob`
- `GenerateBatchExportZipJob`

Rules for jobs:

- idempotent where possible
- update progress + broadcast after DB updates
- meaningful `support_id` on failure

### Events

- `qa.run.progress`, `qa.run.artifact.ready`, `qa.run.completed`, `qa.run.failed`
- `batch.run.progress`, `batch.item.completed`, `batch.run.completed`, `batch.run.failed`

Event schemas live in `04-REALTIME-EVENTS.md`.

---

## Queues (Redis/Horizon)

- `ingest` — small DB + file operations
- `parse` — CPU parsing
- `render` — CPU rendering
- `qa` — rules engine
- `ai` — LLM calls
- `pdf` — report generation
- `export` — batch zips, heavy IO

Never run parse/render/pdf/export synchronously.

---

## Paywall enforcement

All paid features must be enforced server-side using:

- `FeatureGate` (plan limits)
- `CreditsService` (usage-based deductions)

Enforcement points:

- Controllers (request entry)
- Jobs (async re-check)
- Services (debit credits with idempotency)

---

## LLM keys & routing

- Store per-org keys encrypted at rest
- Router selects provider:
    1. org preferred
    2. fallback chain
    3. rules-only summary

Never send raw files to LLM—only structured metrics and findings.

---

## Storage

- Store uploads and artifacts using Laravel filesystem
- Use signed temporary URLs or authorized download routes
- Share links are tokenized + read-only

---

## Failure handling

On failure:

- mark run/batch as failed
- store `error_code` and `support_id`
- broadcast failure event
- show friendly UI message with support id
