<?php

namespace App\Domain\Billing\Services;

use App\Models\Organization;

class FeatureGate
{
    /**
     * Resolve the organization's current plan slug.
     * Falls back to 'free' if the plan is unrecognised or null.
     */
    public function planSlug(Organization $org): string
    {
        $slug = $org->plan_slug ?? 'free';
        $known = array_keys(config('features.plans', []));

        return in_array($slug, $known, true) ? $slug : 'free';
    }

    /**
     * @return array<string, mixed>
     */
    protected function planLimits(Organization $org): array
    {
        $slug = $this->planSlug($org);

        /** @var array<string, mixed> $limits */
        $limits = config("features.plans.{$slug}.limits", config('features.plans.free.limits', []));

        return $limits;
    }

    public function canRunFullRules(Organization $org): bool
    {
        // All plans include full QA rule execution.
        return true;
    }

    public function canUseAiSummary(Organization $org): bool
    {
        return (bool) ($this->planLimits($org)['ai_summary'] ?? false);
    }

    public function canExportPdf(Organization $org): bool
    {
        return (bool) ($this->planLimits($org)['pdf_export'] ?? false);
    }

    /**
     * Returns true when the plan's preset list contains at least one entry.
     */
    public function canUsePresets(Organization $org): bool
    {
        $presets = $this->planLimits($org)['presets'] ?? [];

        return count($presets) > 0;
    }

    public function canRunBatch(Organization $org): bool
    {
        return (bool) ($this->planLimits($org)['batch_enabled'] ?? false);
    }

    public function maxDailyQaRuns(Organization $org): int
    {
        return (int) ($this->planLimits($org)['daily_qa_runs'] ?? 5);
    }

    public function maxFileSizeBytes(Organization $org): int
    {
        $mb = (int) ($this->planLimits($org)['max_file_size_mb'] ?? 10);

        return $mb * 1024 * 1024;
    }

    /**
     * Checks whether the org can start another QA run today.
     *
     * Uses design_files as the proxy for QA run count (one upload = one QA run)
     * until a dedicated qa_runs table exists.  The compound index on
     * (organization_id, created_at) makes this query efficient.
     *
     * Dates are compared in app timezone (config('app.timezone')).
     */
    public function canStartQaRunToday(Organization $org): bool
    {
        $today = now()->toDateString();

        $used = $org->designFiles()
            ->whereDate('created_at', $today)
            ->count();

        return $used < $this->maxDailyQaRuns($org);
    }
}
