<?php

namespace App\Events\QaRun;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QaRunProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $orgId,
        public readonly ?int $actorId,
        public readonly int $qaRunId,
        public readonly string $status,
        public readonly string $stage,
        public readonly int $percent,
        public readonly string $message,
        public readonly array $meta = [],
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('org.'.$this->orgId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'qa.run.progress';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'type' => 'qa.run.progress',
            'ts' => now()->toIso8601String(),
            'org_id' => $this->orgId,
            'actor_id' => $this->actorId,
            'qa_run_id' => $this->qaRunId,
            'status' => $this->status,
            'stage' => $this->stage,
            'percent' => $this->percent,
            'message' => $this->message,
            'meta' => $this->meta,
        ];
    }
}
