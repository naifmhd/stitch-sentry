<?php

namespace App\Http\Controllers\Dev;

use App\Events\QaRun\QaRunProgressEvent;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastTestController extends Controller
{
    public function show(): Response
    {
        $orgId = Organization::first()?->id ?? 1;

        return Inertia::render('dev/ReverbTest', [
            'orgId' => $orgId,
        ]);
    }

    public function broadcast(Request $request): JsonResponse
    {
        $orgId = Organization::first()?->id ?? 1;

        QaRunProgressEvent::dispatch(
            orgId: $orgId,
            actorId: $request->user()->id,
            qaRunId: 999,
            status: 'running',
            stage: 'render',
            percent: 42,
            message: 'Rendering density heatmap',
            meta: ['queue' => 'render'],
        );

        return response()->json(['ok' => true]);
    }
}
