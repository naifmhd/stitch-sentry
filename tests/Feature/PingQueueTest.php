<?php

use App\Jobs\Dev\PingQueueJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('guest is redirected from ping endpoint', function () {
    $this->postJson(route('dev.ping'))->assertUnauthorized();
});

test('authenticated user can dispatch the ping job onto the ingest queue', function () {
    Queue::fake();

    $this->actingAs(User::factory()->create())
        ->postJson(route('dev.ping'))
        ->assertSuccessful()
        ->assertJson(['ok' => true]);

    Queue::assertPushedOn('ingest', PingQueueJob::class);
});
