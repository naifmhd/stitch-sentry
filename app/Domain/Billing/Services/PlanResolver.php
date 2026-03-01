<?php

declare(strict_types=1);

namespace App\Domain\Billing\Services;

use App\Models\Organization;

/**
 * Resolves the current plan slug for an organization.
 *
 * Resolution order:
 *  1. Active Cashier subscription → map price_id from config/paddle_plans.php
 *  2. Fallback to organizations.plan_slug
 *  3. Final fallback: 'free'
 */
class PlanResolver
{
    /**
     * Return the resolved plan slug for the given organization.
     */
    public function resolve(Organization $org): string
    {
        $subscriptionName = config('paddle_plans.subscription_name', 'default');

        // Load the subscription (eager-load items so we avoid N+1).
        $subscription = $org->subscription($subscriptionName);

        if ($subscription && $subscription->active()) {
            $priceId = $subscription->items()->first()?->price_id;

            if ($priceId) {
                $slug = $this->slugForPriceId($priceId);

                if ($slug !== null) {
                    return $slug;
                }
            }
        }

        return $this->fallbackSlug($org);
    }

    /**
     * Map a Paddle price_id back to a plan slug via config/paddle_plans.php.
     * Returns null when no mapping is found.
     */
    public function slugForPriceId(string $priceId): ?string
    {
        /** @var array<string, string|null> $prices */
        $prices = config('paddle_plans.prices', []);

        $slug = array_search($priceId, $prices, true);

        if ($slug === false) {
            return null;
        }

        $knownPlans = array_keys(config('features.plans', []));

        return in_array($slug, $knownPlans, true) ? $slug : null;
    }

    /**
     * Resolve a plan slug from the org's stored column, falling back to 'free'.
     */
    private function fallbackSlug(Organization $org): string
    {
        $slug = $org->plan_slug ?? 'free';
        $known = array_keys(config('features.plans', []));

        return in_array($slug, $known, true) ? $slug : 'free';
    }
}
