<?php

use App\Domain\Billing\Services\FeatureGate;
use App\Models\DesignFile;
use App\Models\Organization;
use App\Models\QaRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ──────────────────────────────────────────────
// POST /upload/ingest — authentication
// ──────────────────────────────────────────────

test('guest cannot upload — redirected to login', function () {
    Storage::fake('s3');

    $file = UploadedFile::fake()->create('design.dst', 10);

    $this->post('/upload/ingest', ['file' => $file])
        ->assertRedirect('/login');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
});

// ──────────────────────────────────────────────
// POST /upload/ingest — validation
// ──────────────────────────────────────────────

test('invalid extension is rejected with a validation error', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $file = UploadedFile::fake()->create('design.png', 10);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
    expect(DesignFile::count())->toBe(0);
});

test('missing file is rejected with a validation error', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', [])
        ->assertSessionHasErrors('file');
});

test('file size limit is enforced via FeatureGate', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    // Bind a mock FeatureGate that enforces a 1 KB limit.
    $gate = Mockery::mock(FeatureGate::class);
    $gate->shouldReceive('maxFileSizeBytes')->andReturn(1_024); // 1 KB
    $gate->shouldReceive('canStartQaRunToday')->andReturn(true);
    $gate->shouldReceive('maxDailyQaRuns')->andReturn(5);
    app()->instance(FeatureGate::class, $gate);

    // Upload a file that is just over the mock limit (2 KB).
    $file = UploadedFile::fake()->create('design.dst', 2);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
    expect(DesignFile::count())->toBe(0);
});

// ──────────────────────────────────────────────
// POST /upload/ingest — successful ingest
// ──────────────────────────────────────────────

test('valid dst file is stored on s3 with the expected path pattern', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $file = UploadedFile::fake()->create('stitch-design.dst', 50); // 50 KB

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file])
        ->assertRedirect();

    $designFile = DesignFile::where('user_id', $user->id)->firstOrFail();

    // Path must follow: organizations/{id}/uploads/{Y}/{m}/{checksum}.dst
    $now = now();
    expect($designFile->storage_path)
        ->toStartWith("organizations/{$org->id}/uploads/{$now->format('Y')}/{$now->format('m')}/")
        ->toEndWith('.dst');

    Storage::disk('s3')->assertExists($designFile->storage_path);
});

test('design_files record is created with correct fields after upload', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $file = UploadedFile::fake()->create('my-design.dst', 10); // 10 KB
    $expectedChecksum = hash_file('sha256', $file->getRealPath());
    $expectedSizeBytes = $file->getSize();

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('design_files', [
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'original_name' => 'my-design.dst',
        'ext' => 'dst',
        'size_bytes' => $expectedSizeBytes,
        'checksum' => $expectedChecksum,
        'status' => 'uploaded',
    ]);

    $designFile = DesignFile::where('user_id', $user->id)->firstOrFail();
    expect($designFile->storage_path)->toContain($expectedChecksum);
});

test('checksum in storage path matches sha256 of the uploaded file', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org, ['role' => 'owner']);

    $file = UploadedFile::fake()->create('test.dst', 5);
    $expectedChecksum = hash_file('sha256', $file->getRealPath());

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file]);

    $designFile = DesignFile::where('user_id', $user->id)->firstOrFail();

    expect($designFile->checksum)->toBe($expectedChecksum)
        ->and($designFile->storage_path)->toContain($expectedChecksum);
});

// ──────────────────────────────────────────────
// POST /upload/ingest — org context
// ──────────────────────────────────────────────

test('upload fails gracefully when user has no organization context', function () {
    Storage::fake('s3');

    // User with no org attached at all.
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('design.dst', 10);

    $this->actingAs($user)
        ->post('/upload/ingest', ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
});

// ──────────────────────────────────────────────
// POST /upload/ingest — daily limit enforcement
// ──────────────────────────────────────────────

test('daily qa run limit blocks further uploads', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $org = Organization::factory()->onPlan('free')->create(); // 5 daily runs
    $user->organizations()->attach($org, ['role' => 'owner']);

    // Exhaust the daily limit by creating 5 qa_run records for today.
    DesignFile::factory()->count(5)->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'created_at' => now(),
    ])->each(function (DesignFile $designFile) use ($org) {
        QaRun::factory()->create([
            'organization_id' => $org->id,
            'design_file_id' => $designFile->id,
            'created_at' => now(),
        ]);
    });

    $file = UploadedFile::fake()->create('design.dst', 10);

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $org->id])
        ->post('/upload/ingest', ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Storage::disk('s3')->allFiles())->toBeEmpty();
});
