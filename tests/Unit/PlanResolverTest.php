<?php

use App\Domain\Billing\Services\PlanResolver;

// Boot the Laravel app so config() helpers work.
uses(Tests\TestCase::class);

// ──────────────────────────────────────────────
// PlanResolver — price_id → plan_slug mapping
// (Pure config-based logic; no database needed.)
// ──────────────────────────────────────────────

beforeEach(function () {
    $this->resolver = new PlanResolver;

    config([
        'paddle_plans.prices' => [
            'starter' => 'pri_starter_test',
            'shop' => 'pri_shop_test',
            'digitizer' => 'pri_digitizer_test',
        ],
    ]);
});

describe('slugForPriceId', function () {
    test('maps a known price_id to the correct plan slug', function (string $slug, string $priceId) {
        expect($this->resolver->slugForPriceId($priceId))->toBe($slug);
    })->with([
        'starter mapping' => ['starter', 'pri_starter_test'],
        'shop mapping' => ['shop', 'pri_shop_test'],
        'digitizer mapping' => ['digitizer', 'pri_digitizer_test'],
    ]);

    test('returns null for an unknown price_id', function () {
        expect($this->resolver->slugForPriceId('pri_unknown_xyz'))->toBeNull();
    });

    test('returns null when price_id maps to a slug absent from features config', function () {
        config(['paddle_plans.prices' => ['ghost_plan' => 'pri_ghost_test']]);

        expect($this->resolver->slugForPriceId('pri_ghost_test'))->toBeNull();
    });

    test('returns null when price IDs config is empty', function () {
        config(['paddle_plans.prices' => []]);

        expect($this->resolver->slugForPriceId('pri_starter_test'))->toBeNull();
    });
});
