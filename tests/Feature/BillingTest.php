<?php

use App\Domain\Billing\Services\FeatureGate;
use App\Domain\Billing\Services\PlanResolver;
use App\Models\Organization;
use App\Models\User;
use Laravel\Paddle\Subscription;
use Laravel\Paddle\SubscriptionItem;

// ──────────────────────────────────────────────
// GET /billing — authentication + rendering
// ──────────────────────────────────────────────

describe('GET /billing', function () {
    test('redirects guests to login', function () {
        $this->get('/billing')->assertRedirect('/login');
    });

    test('renders billing Inertia page for authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('billing/Index'));
    });

    test('billing page includes current plan and paid plans', function () {
        $user = User::factory()->create();
        $org = Organization::factory()->onPlan('starter')->create();
        $org->users()->attach($user, ['role' => 'admin']);

        session(['current_organization_id' => $org->id]);

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('billing/Index')
                    ->has('currentPlanSlug')
                    ->has('paidPlans')
                    ->where('currentPlanSlug', fn ($slug) => in_array($slug, ['free', 'starter', 'shop', 'digitizer']))
            );
    });

    test('billing page lists only paid plans excluding free', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('billing/Index')
                    ->where('paidPlans', function ($plans) {
                        $plans = is_array($plans) ? $plans : $plans->toArray();
                        $slugs = array_column($plans, 'slug');

                        return ! in_array('free', $slugs, true) && count($plans) > 0;
                    })
            );
    });
});

// ──────────────────────────────────────────────
// POST /billing/subscribe/{planSlug} — validation
// ──────────────────────────────────────────────

describe('POST /billing/subscribe/{planSlug}', function () {
    test('rejects invalid plan slug and redirects back with errors', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/billing/subscribe/nonexistent_plan')
            ->assertRedirect()
            ->assertSessionHasErrors(['planSlug']);
    });

    test('rejects free plan slug and redirects back with errors', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/billing/subscribe/free')
            ->assertRedirect()
            ->assertSessionHasErrors(['planSlug']);
    });

    test('redirects guests to login', function () {
        $this->post('/billing/subscribe/starter')->assertRedirect('/login');
    });
});

// ──────────────────────────────────────────────
// Cashier webhook route — CSRF exclusion
// ──────────────────────────────────────────────

describe('Cashier webhook route', function () {
    test('paddle/webhook route exists and accepts POST without CSRF token', function () {
        // We expect either 200 or 400/422 (Paddle signature check fails without
        // real credentials) — NEVER a 419 CSRF mismatch.
        $response = $this->postJson('/paddle/webhook', [], [
            'Paddle-Signature' => 'ts=0;h1=invalid',
        ]);

        expect($response->status())->not->toBe(419);
    });
});

// ──────────────────────────────────────────────
// FeatureGate integration — PlanResolver-first
// ──────────────────────────────────────────────

describe('FeatureGate with PlanResolver integration', function () {
    beforeEach(function () {
        $this->gate = app(FeatureGate::class);

        config([
            'paddle_plans.prices' => [
                'starter' => 'pri_starter_test',
                'shop' => 'pri_shop_test',
                'digitizer' => 'pri_digitizer_test',
            ],
            'paddle_plans.subscription_name' => 'default',
        ]);
    });

    test('when resolver returns starter via active subscription, FeatureGate applies starter limits', function () {
        $org = Organization::factory()->onPlan('free')->create();

        $subscription = Subscription::create([
            'billable_type' => Organization::class,
            'billable_id' => $org->id,
            'type' => 'default',
            'paddle_id' => 'sub_gate_starter',
            'status' => 'active',
        ]);

        SubscriptionItem::create([
            'subscription_id' => $subscription->id,
            'product_id' => 'prod_starter_test',
            'price_id' => 'pri_starter_test',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $org->unsetRelations();

        expect($this->gate->planSlug($org))->toBe('starter')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(200)
            ->and($this->gate->maxFileSizeBytes($org))->toBe(50 * 1024 * 1024)
            ->and($this->gate->canUseAiSummary($org))->toBeTrue()
            ->and($this->gate->canExportPdf($org))->toBeTrue()
            ->and($this->gate->canRunBatch($org))->toBeFalse();
    });

    test('when no subscription exists, FeatureGate falls back to plan_slug column', function () {
        $org = Organization::factory()->onPlan('shop')->create();

        expect($this->gate->planSlug($org))->toBe('shop')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(2000)
            ->and($this->gate->canRunBatch($org))->toBeTrue();
    });

    test('FeatureGate returns free limits when subscription price_id has no mapping', function () {
        $org = Organization::factory()->onPlan('free')->create();

        $subscription = Subscription::create([
            'billable_type' => Organization::class,
            'billable_id' => $org->id,
            'type' => 'default',
            'paddle_id' => 'sub_gate_nomapping',
            'status' => 'active',
        ]);

        SubscriptionItem::create([
            'subscription_id' => $subscription->id,
            'product_id' => 'prod_unmapped_test',
            'price_id' => 'pri_unmapped_price',
            'status' => 'active',
            'quantity' => 1,
        ]);

        $org->unsetRelations();

        // Falls back to org plan_slug = 'free'.
        expect($this->gate->planSlug($org))->toBe('free')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(5);
    });

    test('PlanResolver resolve falls back to plan_slug when no active subscription', function () {
        $resolver = app(PlanResolver::class);

        $org = Organization::factory()->onPlan('digitizer')->create();

        expect($resolver->resolve($org))->toBe('digitizer');
    });
});
