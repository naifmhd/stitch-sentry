# Realtime Events (Laravel Reverb)

All events are broadcast to `private-org.{orgId}` by default.

## Common fields

Every payload includes:

- `type` (string)
- `ts` (ISO8601 string)
- `org_id` (int)
- `actor_id` (int|null)

---

## QA Run Events

### `qa.run.progress`

```json
{
    "type": "qa.run.progress",
    "ts": "2026-02-27T10:00:00Z",
    "org_id": 1,
    "actor_id": 10,
    "qa_run_id": 123,
    "status": "queued|running|completed|failed|completed_with_failures|paused",
    "stage": "ingest|parse|render|qa|ai|pdf|export",
    "percent": 42,
    "message": "Rendering density heatmap",
    "meta": { "queue": "render" }
}
```

### `qa.run.artifact.ready`

```json
{
    "type": "qa.run.artifact.ready",
    "ts": "2026-02-27T10:00:10Z",
    "org_id": 1,
    "actor_id": 10,
    "qa_run_id": 123,
    "artifact": {
        "kind": "preview|density|jumps|pdf",
        "url": "/qa-runs/123/artifacts/preview",
        "meta": { "width": 1200, "height": 1200 }
    }
}
```

### `qa.run.completed`

```json
{
    "type": "qa.run.completed",
    "ts": "2026-02-27T10:00:30Z",
    "org_id": 1,
    "actor_id": 10,
    "qa_run_id": 123,
    "status": "completed|completed_with_failures",
    "score": 86,
    "risk_level": "low|medium|high",
    "summary_available": true,
    "pdf_available": false,
    "meta": { "warnings": 3, "failures": 0 }
}
```

### `qa.run.failed`

```json
{
    "type": "qa.run.failed",
    "ts": "2026-02-27T10:00:20Z",
    "org_id": 1,
    "actor_id": 10,
    "qa_run_id": 123,
    "status": "failed",
    "support_id": "SS-8F2KQ",
    "error_code": "PARSER_TIMEOUT",
    "message": "The parser service timed out while processing this file."
}
```

---

## Batch Events

### `batch.run.progress`

```json
{
    "type": "batch.run.progress",
    "ts": "2026-02-27T10:05:00Z",
    "org_id": 1,
    "actor_id": 10,
    "batch_run_id": 55,
    "status": "queued|running|completed|failed|paused",
    "stage": "ingest|validate|process_items|export_zip",
    "percent": 12,
    "message": "Generating proofs",
    "meta": { "processed": 12, "total": 100 }
}
```

### `batch.item.completed`

```json
{
    "type": "batch.item.completed",
    "ts": "2026-02-27T10:05:10Z",
    "org_id": 1,
    "actor_id": 10,
    "batch_run_id": 55,
    "batch_item_id": 5512,
    "row_index": 12,
    "status": "completed|completed_with_warnings|failed",
    "proof_url": "/batch-items/5512/proof",
    "approval": {
        "status": "pending|approved|revision_requested",
        "public_url": "/approve/abc123"
    },
    "qa": {
        "enabled": false,
        "qa_run_id": null,
        "risk_level": null,
        "score": null
    }
}
```

### `batch.run.completed`

```json
{
    "type": "batch.run.completed",
    "ts": "2026-02-27T10:07:00Z",
    "org_id": 1,
    "actor_id": 10,
    "batch_run_id": 55,
    "status": "completed",
    "export_zip_available": true,
    "export_zip_url": "/batch-runs/55/export"
}
```

### `batch.run.failed`

```json
{
    "type": "batch.run.failed",
    "ts": "2026-02-27T10:06:00Z",
    "org_id": 1,
    "actor_id": 10,
    "batch_run_id": 55,
    "status": "failed",
    "support_id": "SS-BATCH-1H2JD",
    "error_code": "CSV_INVALID",
    "message": "CSV validation failed on row 14 (missing required column)."
}
```

---

## Delivery rules

- Always broadcast after DB updates.
- `percent` must never go backwards.
- Frontend must fetch current state on page load, then apply realtime updates.
