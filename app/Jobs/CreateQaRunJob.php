<?php

namespace App\Jobs;

use App\Events\QaRun\QaRunProgressEvent;
use App\Models\QaRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateQaRunJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly QaRun $qaRun)
    {
        $this->onQueue('ingest');
    }

    /**
     * Execute the job — pipeline starter (ingest + parse stub stages).
     */
    public function handle(): void
    {
        $qaRun = $this->qaRun->fresh();

        if (! $qaRun) {
            Log::warning('CreateQaRunJob: QaRun not found', ['id' => $this->qaRun->id]);

            return;
        }

        // Stage 1: ingest
        $qaRun->update([
            'status' => 'running',
            'stage' => 'ingest',
            'progress' => 5,
            'started_at' => now(),
        ]);

        $this->broadcastProgress($qaRun, 'Ingesting design file…');

        // Stage 2: parse (stub — ParseEmbroideryFileJob dispatched in Epic 3)
        $qaRun->update([
            'stage' => 'parse',
            'progress' => 10,
        ]);

        $this->broadcastProgress($qaRun, 'Parsing embroidery file…');

        // TODO (ISSUE 3.2): dispatch ParseEmbroideryFileJob::dispatch($qaRun)
        // TODO (ISSUE 3.3): dispatch RenderPreviewsJob
        // TODO (ISSUE 4.2): dispatch RunRuleQaJob
        // TODO (ISSUE 5.x): dispatch GenerateAiSummaryJob if canUseAiSummary
        // TODO (ISSUE 6.x): dispatch GeneratePdfReportJob if canExportPdf
        // TODO (ISSUE 6.x): dispatch export stage
    }

    private function broadcastProgress(QaRun $qaRun, string $message): void
    {
        /** @var QaRun $freshRun */
        $freshRun = $qaRun->fresh() ?? $qaRun;

        event(new QaRunProgressEvent(
            orgId: $freshRun->organization_id,
            actorId: null,
            qaRunId: $freshRun->id,
            status: $freshRun->status,
            stage: (string) $freshRun->stage,
            percent: $freshRun->progress,
            message: $message,
            meta: ['queue' => 'ingest'],
        ));
    }
}
