<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Jobs\Dev\PingQueueJob;
use Illuminate\Http\JsonResponse;

class PingQueueController extends Controller
{
    /**
     * Dispatch the PingQueueJob and return a JSON confirmation.
     */
    public function __invoke(): JsonResponse
    {
        PingQueueJob::dispatch();

        return response()->json(['ok' => true]);
    }
}
