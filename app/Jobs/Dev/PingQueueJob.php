<?php

namespace App\Jobs\Dev;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PingQueueJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('ingest');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('PingQueueJob executed', [
            'queue' => $this->queue ?? 'ingest',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
