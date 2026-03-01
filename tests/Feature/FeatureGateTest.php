<?php

use App\Domain\Billing\Services\FeatureGate;
use App\Models\Organization;
use App\Models\QaRun;
use App\Models\User;

// ──────────────────────────────────────────────
// FeatureGate — plan flag / limit correctness
// ──────────────────────────────────────────────

describe('FeatureGate plan flags', function () {
    beforeEach(function () {
        $this->gate = app(FeatureGate::class);
    });

    test('free plan has correct limits', function () {
        $org = Organization::factory()->onPlan('free')->create();

        expect($this->gate->planSlug($org))->toBe('free')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(5)
            ->and($this->gate->maxFileSizeBytes($org))->toBe(10 * 1024 * 1024)
            ->and($this->gate->canRunFullRules($org))->toBeTrue()
            ->and($this->gate->canUseAiSummary($org))->toBeFalse()
            ->and($this->gate->canExportPdf($org))->toBeFalse()
            ->and($this->gate->canRunBatch($org))->toBeFalse()
            ->and($this->gate->canUsePresets($org))->toBeTrue(); // free still has ['custom']
    });

    test('starter plan has correct limits', function () {
        $org = Organization::factory()->onPlan('starter')->create();

        expect($this->gate->planSlug($org))->toBe('starter')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(200)
            ->and($this->gate->maxFileSizeBytes($org))->toBe(50 * 1024 * 1024)
            ->and($this->gate->canUseAiSummary($org))->toBeTrue()
            ->and($this->gate->canExportPdf($org))->toBeTrue()
            ->and($this->gate->canRunBatch($org))->toBeFalse()
            ->and($this->gate->canUsePresets($org))->toBeTrue();
    });

    test('shop plan has correct limits', function () {
        $org = Organization::factory()->onPlan('shop')->create();

        expect($this->gate->planSlug($org))->toBe('shop')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(2000)
            ->and($this->gate->maxFileSizeBytes($org))->toBe(100 * 1024 * 1024)
            ->and($this->gate->canRunBatch($org))->toBeTrue()
            ->and($this->gate->canUseAiSummary($org))->toBeTrue()
            ->and($this->gate->canExportPdf($org))->toBeTrue();
    });

    test('digitizer plan has correct limits', function () {
        $org = Organization::factory()->onPlan('digitizer')->create();

        expect($this->gate->planSlug($org))->toBe('digitizer')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(10000)
            ->and($this->gate->maxFileSizeBytes($org))->toBe(250 * 1024 * 1024)
            ->and($this->gate->canRunBatch($org))->toBeTrue()
            ->and($this->gate->canUseAiSummary($org))->toBeTrue()
            ->and($this->gate->canExportPdf($org))->toBeTrue();
    });

    test('unknown plan slug falls back to free limits', function () {
        $org = Organization::factory()->create(['plan_slug' => 'enterprise_unknown']);

        expect($this->gate->planSlug($org))->toBe('free')
            ->and($this->gate->maxDailyQaRuns($org))->toBe(5);
    });

    test('canRunFullRules is true for every plan', function () {
        foreach (['free', 'starter', 'shop', 'digitizer'] as $plan) {
            $org = Organization::factory()->onPlan($plan)->create();
            expect($this->gate->canRunFullRules($org))->toBeTrue("Expected canRunFullRules=true for {$plan}");
        }
    });
});

// ──────────────────────────────────────────────
// FeatureGate — daily run counting
// ──────────────────────────────────────────────

describe('FeatureGate daily run limit', function () {
    beforeEach(function () {
        $this->gate = app(FeatureGate::class);
    });

    test('canStartQaRunToday returns true when under limit', function () {
        $org = Organization::factory()->onPlan('free')->create(); // limit = 5

        // 4 qa runs today
        QaRun::factory()->count(4)->create([
            'organization_id' => $org->id,
            'created_at' => now(),
        ]);

        expect($this->gate->canStartQaRunToday($org))->toBeTrue();
    });

    test('canStartQaRunToday returns false when limit is reached', function () {
        $org = Organization::factory()->onPlan('free')->create(); // limit = 5

        // 5 qa runs today — limit exactly hit
        QaRun::factory()->count(5)->create([
            'organization_id' => $org->id,
            'created_at' => now(),
        ]);

        expect($this->gate->canStartQaRunToday($org))->toBeFalse();
    });

    test('yesterday uploads do not count against today limit', function () {
        $org = Organization::factory()->onPlan('free')->create(); // limit = 5

        // 5 qa runs yesterday
        QaRun::factory()->count(5)->create([
            'organization_id' => $org->id,
            'created_at' => now()->subDay(),
        ]);

        expect($this->gate->canStartQaRunToday($org))->toBeTrue();
    });

    test('limit increases when org is upgraded to starter', function () {
        $org = Organization::factory()->onPlan('starter')->create(); // limit = 200

        // 5 qa runs today — free limit, but org is on starter
        QaRun::factory()->count(5)->create([
            'organization_id' => $org->id,
            'created_at' => now(),
        ]);

        expect($this->gate->canStartQaRunToday($org))->toBeTrue();
    });
});

// ──────────────────────────────────────────────
// FeatureGate — HTTP enforcement endpoint
// ──────────────────────────────────────────────

describe('POST /dev/feature-gate-check', function () {
    test('returns 422 when no organization is attached', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/dev/feature-gate-check', ['file_size_bytes' => 1024])
            ->assertStatus(422)
            ->assertJson(['error' => 'No active organization.']);
    });

    test('returns 200 when within all limits', function () {
        $org = Organization::factory()->onPlan('free')->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org, ['role' => 'owner']);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->postJson('/dev/feature-gate-check', [
                'file_size_bytes' => 5 * 1024 * 1024, // 5 MB, limit is 10 MB
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'plan' => 'free']);
    });

    test('returns 422 when file size exceeds plan limit', function () {
        $org = Organization::factory()->onPlan('free')->create(); // max 10 MB
        $user = User::factory()->create();
        $user->organizations()->attach($org, ['role' => 'owner']);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->postJson('/dev/feature-gate-check', [
                'file_size_bytes' => 15 * 1024 * 1024, // 15 MB — over limit
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 'file_too_large']);
    });

    test('returns 429 when daily run limit is exceeded on free plan', function () {
        $org = Organization::factory()->onPlan('free')->create(); // limit = 5
        $user = User::factory()->create();
        $user->organizations()->attach($org, ['role' => 'owner']);

        // Exhaust today's quota
        QaRun::factory()->count(5)->create([
            'organization_id' => $org->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->postJson('/dev/feature-gate-check', ['file_size_bytes' => 1024])
            ->assertStatus(429)
            ->assertJsonFragment([
                'code' => 'daily_limit_reached',
                'max_daily_qa_runs' => 5,
            ]);
    });

    test('starter plan allows more than 5 runs per day', function () {
        $org = Organization::factory()->onPlan('starter')->create(); // limit = 200
        $user = User::factory()->create();
        $user->organizations()->attach($org, ['role' => 'owner']);

        // 5 qa runs that would block a free org
        QaRun::factory()->count(5)->create([
            'organization_id' => $org->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->postJson('/dev/feature-gate-check', ['file_size_bytes' => 1024])
            ->assertOk()
            ->assertJsonFragment(['plan' => 'starter']);
    });

    test('unauthenticated request is redirected', function () {
        $this->postJson('/dev/feature-gate-check', ['file_size_bytes' => 1024])
            ->assertUnauthorized();
    });
});
