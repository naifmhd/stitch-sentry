<?php

use App\Domain\Billing\Services\CreditsService;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ──────────────────────────────────────────────
// CreditsService — unit-level database tests
// ──────────────────────────────────────────────

test('starting credit balance is zero', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    expect($service->balance($org))->toBe(0);
});

test('credit increases balance', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    $service->credit($org, 100, 'Initial top-up');

    expect($service->balance($org))->toBe(100);
});

test('debit decreases balance', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    $service->credit($org, 100, 'Initial top-up');
    $service->debit($org, 40, 'QA run cost');

    expect($service->balance($org))->toBe(60);
});

test('debit cannot exceed available balance', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    $service->credit($org, 50, 'Small credit');

    expect(fn () => $service->debit($org, 100, 'Over-draw attempt'))
        ->toThrow(DomainException::class, 'Insufficient credits');

    expect($service->balance($org))->toBe(50);
});

test('debit rejects non-positive amount', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    expect(fn () => $service->debit($org, 0, 'Zero debit'))
        ->toThrow(InvalidArgumentException::class, 'Debit amount must be greater than zero');
});

test('credit is idempotent when same key used twice', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    $key = 'promo-2026-q1';
    $firstEntry = $service->credit($org, 200, 'Promo credit', [], $key);
    $secondEntry = $service->credit($org, 200, 'Promo credit', [], $key);

    expect($firstEntry->id)->toBe($secondEntry->id);
    expect($service->balance($org))->toBe(200);
});

test('debit is idempotent when same key used twice', function () {
    $org = Organization::factory()->create();
    $service = app(CreditsService::class);

    $service->credit($org, 100, 'Initial credit');

    $key = 'qa-run-001';
    $firstEntry = $service->debit($org, 30, 'QA run', [], $key);
    $secondEntry = $service->debit($org, 30, 'QA run', [], $key);

    expect($firstEntry->id)->toBe($secondEntry->id);
    expect($service->balance($org))->toBe(70);
});
