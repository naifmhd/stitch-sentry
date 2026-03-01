<?php

use App\Events\QaRun\QaRunProgressEvent;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('guest is redirected from reverb test page', function () {
    $this->get(route('dev.reverb-test'))->assertRedirect();
});

test('guest is unauthorized on broadcast endpoint', function () {
    $this->postJson(route('dev.broadcast-test'))->assertUnauthorized();
});

test('authenticated user can broadcast a qa run progress event', function () {
    Event::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('dev.broadcast-test'))
        ->assertSuccessful()
        ->assertJson(['ok' => true]);

    Event::assertDispatched(QaRunProgressEvent::class, function (QaRunProgressEvent $event) {
        $payload = $event->broadcastWith();

        return $payload['type'] === 'qa.run.progress'
            && $payload['status'] === 'running'
            && $payload['stage'] === 'render'
            && $payload['percent'] === 42
            && $payload['qa_run_id'] === 999
            && $event->broadcastAs() === 'qa.run.progress';
    });
});
