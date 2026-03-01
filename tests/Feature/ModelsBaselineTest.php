<?php

use App\Models\DesignFile;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

test('organization can have many projects', function () {
    $organization = Organization::factory()->create();
    Project::factory(3)->create(['organization_id' => $organization->id]);

    expect($organization->projects()->count())->toBe(3);
});

test('organization can have many design files', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    DesignFile::factory(2)->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
    ]);

    expect($organization->designFiles()->count())->toBe(2);
});

test('project belongs to an organization', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    expect($project->organization->id)->toBe($organization->id);
});

test('project can have many design files', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    DesignFile::factory(2)->create([
        'organization_id' => $project->organization_id,
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($project->designFiles()->count())->toBe(2);
});

test('design file can be created with organization and user', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $designFile = DesignFile::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
    ]);

    expect($designFile->organization->id)->toBe($organization->id)
        ->and($designFile->user->id)->toBe($user->id)
        ->and($designFile->project_id)->toBeNull();
});

test('design file project_id is nullable', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $designFile = DesignFile::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'project_id' => null,
    ]);

    expect($designFile->project_id)->toBeNull()
        ->and($designFile->project)->toBeNull();
});

test('design file belongs to a project when project_id is set', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $designFile = DesignFile::factory()->create([
        'organization_id' => $project->organization_id,
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);

    expect($designFile->project->id)->toBe($project->id);
});

test('user can have many design files', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    DesignFile::factory(3)->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    expect($user->designFiles()->count())->toBe(3);
});
