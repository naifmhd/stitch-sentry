# QA Rules (v1)

This document defines the **rule keys**, what each rule checks, typical causes, and recommended actions.
Rule keys must remain stable (used in DB, UI, analytics).

---

## Severity definitions

- **pass**: No action needed
- **warn**: Likely production risk depending on machine/fabric; review recommended
- **fail**: High risk; do not stitch without changes

---

## Presets

- `left_chest`
- `cap`
- `patch`
- `custom`

Presets load thresholds from `config/qa.php`.

---

## Rule registry (v1)

### 1) `hoop_limit_exceeded`

**Checks**

- design width/height exceeds preset hoop limits

**Evidence**

- `width_mm`, `height_mm`, `limit_width_mm`, `limit_height_mm`

**Recommendation**

- Reduce design size, split into multiple hoopings, or pick a larger hoop preset.

---

### 2) `excessive_jump_count`

**Checks**

- `jump_count` exceeds warn/fail thresholds

**Why it matters**

- More jumps = more trims/manual cutting; higher chance of ugly travel threads.

**Evidence**

- `jump_count`, `warn_threshold`, `fail_threshold`

**Recommendation**

- Re-route digitizing to minimize travel, combine objects, avoid unnecessary stops.

---

### 3) `longest_jump_too_large`

**Checks**

- `longest_jump_mm` exceeds thresholds

**Why it matters**

- Long travel stitches are prone to snagging and visible thread lines.

**Evidence**

- `longest_jump_mm`, thresholds

**Recommendation**

- Add trims, reorder stitching sequence, place nearest objects together.

---

### 4) `too_many_color_changes`

**Checks**

- `color_changes` exceeds thresholds

**Why it matters**

- Slows production; more operator intervention.

**Evidence**

- `color_changes`, thresholds

**Recommendation**

- Merge same-color regions, reduce unnecessary stops, simplify palette.

---

### 5) `min_stitch_length_too_short`

**Checks**

- minimum stitch length below thresholds

**Why it matters**

- Very short stitches can lead to thread breaks and needle heat.

**Evidence**

- `min_stitch_length_mm`, thresholds, histogram snapshot (optional)

**Recommendation**

- Adjust digitizing: reduce detail density, increase satin step/spacing, simplify tiny corners.

---

### 6) `density_hotspots`

**Checks**

- tile-based density proxy exceeds thresholds in too many tiles

**Why it matters**

- High-density regions can cause puckering, stiffness, thread breaks.

**Evidence**

- tile size, hotspot threshold, hotspot tile count, top hotspot coordinates (optional)

**Recommendation**

- Reduce density, add correct underlay, consider fabric/stabilizer changes, avoid repeated overlaps.

---

### 7) `tiny_text_risk`

**Checks**

- heuristic: small overall height + density hotspot threshold exceeded
- (later: detect text-like regions from vector data if available)

**Why it matters**

- Small lettering is the #1 failure mode for hobby files and cheap digitizing.

**Evidence**

- `height_mm`, density threshold, hotspot info

**Recommendation**

- Increase text height, switch to a stitch-safe font, reduce density, use proper underlay and stabilizer.

---

## Scoring (v1)

Score = `base_score - (warn_count * warn_weight) - (fail_count * fail_weight)`

Risk level suggestion:

- 85–100: low
- 60–84: medium
- 0–59: high

(These boundaries can be tuned later using user feedback outcomes.)
