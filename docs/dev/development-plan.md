# Full Development Plan (Laravel + Inertia + MySQL + Queues + Reverb)

This plan is designed to ship value quickly, while keeping the system scalable and premium.

---

## Phase 0 — Product foundations (Week 1)

**Goal:** project boots, realtime works, queues work.

- Laravel + Inertia + Vue3 TS + Tailwind
- Auth scaffolding
- MySQL + migrations baseline
- Redis + Horizon
- Reverb + Echo client
- Base UI layout (premium design system setup)

Deliverables:

- working local dev environment
- sample realtime event page
- sample queued job page

---

## Phase 1 — QA MVP (Weeks 2–4)

**Goal:** Upload file → automated QA report → realtime progress.

### Backend

- `design_files` upload + storage abstraction
- `qa_runs` pipeline:
    - ingest → parse → render → rules
- ParserClient (HTTP) + signed requests
- Store metrics, findings, artifacts
- Score calculation (0–100) and risk level
- Share links (optional in MVP)

### Frontend

- Upload page
- QA run live page:
    - stage timeline + progress bar (realtime)
    - preview/density/jumps tabs
    - findings grouped by severity
    - score gauge (premium component)
- Projects/history list

Deliverables:

- fully automated QA run with Reverb progress events
- premium report UI (even before PDF export)

---

## Phase 2 — Paywall + Billing (Weeks 4–6)

**Goal:** Monetize the QA tool.

- Organizations + roles
- Plans & FeatureGate
- Credits ledger + purchase flow (Stripe)
- Enforce paywall server-side on:
    - presets beyond free
    - AI summary
    - PDF export
    - higher usage limits

Deliverables:

- Billing page
- credit balance UI + purchase
- enforceable plan limits

---

## Phase 3 — AI summaries (Weeks 6–7)

**Goal:** Add “AI + rules” premium value.

- LLM provider abstraction:
    - OpenAI / Gemini / Anthropic
- Per-org encrypted API keys
- Router fallback chain
- GenerateAiSummaryJob (paywalled)
- Strict JSON validation of AI output

Deliverables:

- AI summary displayed as:
    - risk level + top actions
    - operator checklist
    - “message to digitizer” copy button

---

## Phase 4 — Premium PDF exports (Weeks 7–8)

**Goal:** Export a professional report that feels like “money’s worth”.

- Premium PDF template:
    - cover page
    - score
    - top actions
    - findings table
    - embedded images
    - operator checklist
- GeneratePdfReportJob (paywalled)
- Download link appears live when ready

---

## Phase 5 — Batch personalization add-on (Weeks 9–12)

**Goal:** CSV → proofs → approvals → export pack.

- Templates:
    - base design + placeholder config
- CSV upload + mapping
- ProcessBatchRunJob:
    - generate proofs
    - approval token per item
    - realtime per-item updates
- Export zip pack (proofs + job sheet)
- Credits per row

Deliverables:

- batch pipeline fully automated with realtime UI
- approvals portal public page

---

## Phase 6 — Reliability, admin, and scaling (ongoing)

- Admin panel: plan limits, provider defaults, outage toggles
- Observability: job failures, support ids, usage dashboards
- Rate limits + abuse protection
- Multi-queue tuning and “rush” processing

---

## Recommended MVP cut (fastest revenue)

Ship in this order:

1. QA upload → parse → render → rules + premium UI
2. Billing + FeatureGate + PDF export
3. AI summary (provider switching)
4. Batch proofs + approvals (then export zip)
