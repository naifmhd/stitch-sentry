<?php

use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// ──────────────────────────────────────────────
// Relationships
// ──────────────────────────────────────────────

test('user can belong to many organizations', function () {
    $user = User::factory()->create();
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user->organizations()->attach($orgA, ['role' => 'owner']);
    $user->organizations()->attach($orgB, ['role' => 'member']);

    expect($user->organizations()->count())->toBe(2);
});

test('organization can have many users with roles', function () {
    $org = Organization::factory()->create();
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $org->users()->attach($owner, ['role' => 'owner']);
    $org->users()->attach($member, ['role' => 'member']);

    expect($org->users()->count())->toBe(2);
    expect($org->users()->where('users.id', $owner->id)->first()?->pivot->role)->toBe('owner');
});

test('isMemberOfOrganization returns true for members', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();

    $user->organizations()->attach($org, ['role' => 'member']);

    expect($user->isMemberOfOrganization($org))->toBeTrue();
});

test('isMemberOfOrganization returns false for non-members', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();

    expect($user->isMemberOfOrganization($org))->toBeFalse();
});

// ──────────────────────────────────────────────
// Shared props — org data appears for auth users
// ──────────────────────────────────────────────

test('authenticated user sees currentOrganization in shared props', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();

    $user->organizations()->attach($org, ['role' => 'owner']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->has('currentOrganization')
                ->where('currentOrganization.id', $org->id)
                ->where('currentOrganization.name', $org->name)
                ->has('organizations', 1)
        );
});

test('organizations list is empty in shared props when user has no org', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->where('currentOrganization', null)
                ->has('organizations', 0)
        );
});

// ──────────────────────────────────────────────
// Org switcher
// ──────────────────────────────────────────────

test('user can switch to an organization they belong to', function () {
    $user = User::factory()->create();
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user->organizations()->attach($orgA, ['role' => 'owner']);
    $user->organizations()->attach($orgB, ['role' => 'member']);

    $this->actingAs($user)
        ->post('/organizations/switch', ['organization_id' => $orgB->id])
        ->assertRedirect();

    expect(session('current_organization_id'))->toBe($orgB->id);
});

test('switching org updates the currentOrganization shared prop', function () {
    $user = User::factory()->create();
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user->organizations()->attach($orgA, ['role' => 'owner']);
    $user->organizations()->attach($orgB, ['role' => 'member']);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $orgA->id])
        ->post('/organizations/switch', ['organization_id' => $orgB->id]);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $orgB->id])
        ->get('/dashboard')
        ->assertInertia(
            fn (Assert $page) => $page
                ->where('currentOrganization.id', $orgB->id)
        );
});

test('user cannot switch to an organization they are not a member of', function () {
    $user = User::factory()->create();
    $orgA = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();

    $user->organizations()->attach($orgA, ['role' => 'owner']);

    $this->actingAs($user)
        ->post('/organizations/switch', ['organization_id' => $otherOrg->id])
        ->assertForbidden();
});

test('guests cannot switch organization', function () {
    $org = Organization::factory()->create();

    $this->post('/organizations/switch', ['organization_id' => $org->id])
        ->assertRedirect('/login');
});

// ──────────────────────────────────────────────
// Org settings page
// ──────────────────────────────────────────────

test('member can view org settings page', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();

    $user->organizations()->attach($org, ['role' => 'member']);

    $this->actingAs($user)
        ->get("/organizations/{$org->id}/settings")
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('organizations/Settings')
                ->where('organization.id', $org->id)
                ->has('members')
        );
});

test('non-member is forbidden from org settings page', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();

    $this->actingAs($user)
        ->get("/organizations/{$org->id}/settings")
        ->assertForbidden();
});

test('org settings members list includes role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->create();

    $org->users()->attach($owner, ['role' => 'owner']);
    $org->users()->attach($member, ['role' => 'member']);

    $this->actingAs($owner)
        ->get("/organizations/{$org->id}/settings")
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->has('members', 2)
                ->where('members.0.role', 'owner')
        );
});
