<?php

use App\Events\QaRun\QaRunProgressEvent;
use App\Jobs\CreateQaRunJob;
use App\Models\DesignFile;
use App\Models\Organization;
use App\Models\QaRun;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

// ──────────────────────────────────────────────
// POST /design-files/{designFile}/qa-runs — auth
// ──────────────────────────────────────────────

test('guest cannot create a qa run', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);
    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);

    $this->post(route('qa-runs.store', $designFile))
        ->assertRedirect('/login');

    expect(QaRun::count())->toBe(0);
});

// ──────────────────────────────────────────────
// POST /design-files/{designFile}/qa-runs — store
// ──────────────────────────────────────────────

test('creating a qa run inserts a row with correct org and design file', function () {
    Queue::fake();

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post(route('qa-runs.store', $designFile))
        ->assertRedirect();

    $qaRun = QaRun::first();

    expect($qaRun)->not->toBeNull()
        ->and($qaRun->organization_id)->toBe($org->id)
        ->and($qaRun->design_file_id)->toBe($designFile->id)
        ->and($qaRun->status)->toBe('queued')
        ->and($qaRun->preset)->toBe('custom');
});

test('creating a qa run dispatches CreateQaRunJob on the ingest queue', function () {
    Queue::fake();

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post(route('qa-runs.store', $designFile));

    Queue::assertPushedOn('ingest', CreateQaRunJob::class);
});

test('a design file from another org cannot be used to create a qa run', function () {
    Queue::fake();

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $otherOrg = Organization::factory()->create();
    $otherUser = User::factory()->create();
    $designFile = DesignFile::factory()->create([
        'organization_id' => $otherOrg->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post(route('qa-runs.store', $designFile))
        ->assertForbidden();

    expect(QaRun::count())->toBe(0);
    Queue::assertNothingPushed();
});

// ──────────────────────────────────────────────
// GET /qa-runs/{qaRun} — show
// ──────────────────────────────────────────────

test('guest cannot view a qa run show page', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);
    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);
    $qaRun = QaRun::factory()->create([
        'organization_id' => $org->id,
        'design_file_id' => $designFile->id,
    ]);

    $this->get(route('qa-runs.show', $qaRun))
        ->assertRedirect('/login');
});

test('authenticated user can view their own qa run show page', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);
    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);
    $qaRun = QaRun::factory()->create([
        'organization_id' => $org->id,
        'design_file_id' => $designFile->id,
    ]);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->get(route('qa-runs.show', $qaRun))
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
            ->component('qa-runs/Show')
            ->has('qaRun')
            ->where('qaRun.id', $qaRun->id)
            ->where('qaRun.status', 'queued')
        );
});

test('user cannot view a qa run belonging to another org', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $otherOrg = Organization::factory()->create();
    $otherUser = User::factory()->create();
    $designFile = DesignFile::factory()->create([
        'organization_id' => $otherOrg->id,
        'user_id' => $otherUser->id,
    ]);
    $qaRun = QaRun::factory()->create([
        'organization_id' => $otherOrg->id,
        'design_file_id' => $designFile->id,
    ]);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->get(route('qa-runs.show', $qaRun))
        ->assertForbidden();
});

// ──────────────────────────────────────────────
// CreateQaRunJob — DB state + broadcasting
// ──────────────────────────────────────────────

test('CreateQaRunJob updates qa_run status and stage in DB', function () {
    Event::fake();

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);
    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);
    $qaRun = QaRun::factory()->create([
        'organization_id' => $org->id,
        'design_file_id' => $designFile->id,
        'status' => 'queued',
    ]);

    (new CreateQaRunJob($qaRun))->handle();

    $qaRun->refresh();

    expect($qaRun->status)->toBe('running')
        ->and($qaRun->stage)->toBe('parse')  // final stage after stub
        ->and($qaRun->progress)->toBe(10)
        ->and($qaRun->started_at)->not->toBeNull();
});

test('CreateQaRunJob broadcasts qa.run.progress events', function () {
    Event::fake();

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);
    $designFile = DesignFile::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
    ]);
    $qaRun = QaRun::factory()->create([
        'organization_id' => $org->id,
        'design_file_id' => $designFile->id,
        'status' => 'queued',
    ]);

    (new CreateQaRunJob($qaRun))->handle();

    Event::assertDispatched(QaRunProgressEvent::class, function (QaRunProgressEvent $event) use ($qaRun, $org) {
        $payload = $event->broadcastWith();

        $requiredKeys = ['type', 'ts', 'org_id', 'actor_id', 'status', 'stage', 'percent', 'message', 'meta'];
        $allKeysPresent = count(array_diff($requiredKeys, array_keys($payload))) === 0;

        return $payload['qa_run_id'] === $qaRun->id
            && $payload['org_id'] === $org->id
            && $payload['type'] === 'qa.run.progress'
            && $allKeysPresent;
    });
});

// ──────────────────────────────────────────────
// POST /upload/ingest — dispatches CreateQaRunJob
// ──────────────────────────────────────────────

test('uploading a file dispatches CreateQaRunJob after creating design file and qa run', function () {
    Queue::fake();
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $file = \Illuminate\Http\UploadedFile::fake()->create('design.dst', 10);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file])
        ->assertRedirect();

    Queue::assertPushedOn('ingest', CreateQaRunJob::class);

    expect(QaRun::count())->toBe(1)
        ->and(QaRun::first()->organization_id)->toBe($org->id);
});
