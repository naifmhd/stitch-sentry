<?php

namespace App\Http\Controllers\QaRun;

use App\Domain\Billing\Services\FeatureGate;
use App\Http\Controllers\Controller;
use App\Http\Requests\QaRun\CreateQaRunRequest;
use App\Jobs\CreateQaRunJob;
use App\Models\DesignFile;
use App\Models\QaRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QaRunController extends Controller
{
    public function __construct(private readonly FeatureGate $featureGate)
    {
    }

    /**
     * Create a new QA run for the given design file and dispatch the pipeline starter job.
     */
    public function store(CreateQaRunRequest $request, DesignFile $designFile): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        if (! $org || ! $user->isMemberOfOrganization($org)) {
            return back()->withErrors(['file' => 'No organization context.']);
        }

        if ($designFile->organization_id !== $org->id) {
            abort(403, 'This file does not belong to your organization.');
        }

        if (! $this->featureGate->canStartQaRunToday($org)) {
            return back()->withErrors([
                'file' => sprintf(
                    'Daily QA run limit (%d) reached for your plan.',
                    $this->featureGate->maxDailyQaRuns($org)
                ),
            ]);
        }

        $preset = $request->input('preset', 'custom');

        $qaRun = QaRun::create([
            'organization_id' => $org->id,
            'design_file_id' => $designFile->id,
            'preset' => $preset,
            'status' => 'queued',
        ]);

        CreateQaRunJob::dispatch($qaRun)->onQueue('ingest');

        return to_route('qa-runs.show', $qaRun);
    }

    /**
     * Display the live QA run page.
     */
    public function show(Request $request, QaRun $qaRun): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        if (! $org || $qaRun->organization_id !== $org->id) {
            abort(403, 'Access denied.');
        }

        $qaRun->load(['designFile', 'findings', 'artifacts']);

        return Inertia::render('qa-runs/Show', [
            'qaRun' => [
                'id' => $qaRun->id,
                'status' => $qaRun->status,
                'stage' => $qaRun->stage,
                'progress' => $qaRun->progress,
                'preset' => $qaRun->preset,
                'score' => $qaRun->score,
                'risk_level' => $qaRun->risk_level,
                'error_code' => $qaRun->error_code,
                'support_id' => $qaRun->support_id,
                'started_at' => $qaRun->started_at?->toIso8601String(),
                'finished_at' => $qaRun->finished_at?->toIso8601String(),
                'design_file' => [
                    'id' => $qaRun->designFile->id,
                    'original_name' => $qaRun->designFile->original_name,
                    'ext' => $qaRun->designFile->ext,
                ],
            ],
            'findings' => [],
            'artifacts' => [],
            'orgId' => $org->id,
        ]);
    }
}
