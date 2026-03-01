<?php

use App\Domain\Billing\Services\CreditsService;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// ──────────────────────────────────────────────
// Credits page — feature tests
// ──────────────────────────────────────────────

test('authenticated user can view credits page', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $this->actingAs($user)
        ->get(route('billing.credits', $org))
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('billing/Credits')
                ->has('balance')
                ->has('entries')
                ->where('balance', 0)
        );
});

test('credits page reflects current balance', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $service = app(CreditsService::class);
    $service->credit($org, 150, 'Welcome bonus');

    $this->actingAs($user)
        ->get(route('billing.credits', $org))
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('billing/Credits')
                ->where('balance', 150)
                ->has('entries', 1)
        );
});

test('guest cannot access credits page', function () {
    $org = Organization::factory()->create();

    $this->get(route('billing.credits', $org))
        ->assertRedirect(route('login'));
});

test('credits balance appears in shared inertia props for authenticated user', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $service = app(CreditsService::class);
    $service->credit($org, 75, 'Test credit');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->has('creditsBalance')
                ->where('creditsBalance', 75)
        );
});
