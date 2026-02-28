## Summary

<!-- What does this PR change? Keep it short. -->

## Issue

Closes #

## What changed

- [ ] Backend
- [ ] Frontend
- [ ] Migrations
- [ ] Queues / Horizon
- [ ] Reverb realtime
- [ ] Paywall / FeatureGate / Credits
- [ ] LLM Provider routing
- [ ] PDF export
- [ ] Docs update (existing docs only)

## Acceptance Criteria

<!-- Paste the acceptance criteria from the issue and confirm each item. -->

- [ ] …

## Testing

- [ ] `php artisan migrate:fresh --seed`
- [ ] `php artisan test --compact` (include file/filter used)
- [ ] `npm run build` (or `npm run dev` sanity)
- [ ] `vendor/bin/pint --dirty --format agent` (if PHP changed)

### Test output (paste)

```text
(paste here)
```

## Realtime/Event Schema Checklist (if applicable)

- [ ] Event payloads match `docs/dev/realtime-events.md` exactly
- [ ] Progress events broadcast AFTER DB updates
- [ ] UI fetches initial state via HTTP then applies realtime updates

## Paywall Checklist (if applicable)

- [ ] FeatureGate enforced server-side in controllers
- [ ] FeatureGate re-checked inside jobs (async safety)
- [ ] Credits debit uses idempotency_key

## Screenshots / Recording (if UI changed)

<!-- Add screenshots or short recording. -->

## Notes / Follow-ups

<!-- Anything to tackle later. -->
