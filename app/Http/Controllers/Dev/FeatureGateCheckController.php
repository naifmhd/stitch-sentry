<?php

namespace App\Http\Controllers\Dev;

use App\Domain\Billing\Services\FeatureGate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dev-only endpoint demonstrating FeatureGate enforcement.
 *
 * POST /dev/feature-gate-check
 *   Body: { "file_size_bytes": 12345678 }
 *
 * Returns 422 when the file exceeds the plan's max size.
 * Returns 429 when the org has exhausted its daily QA run allowance.
 * Returns 200 with plan details on success.
 */
class FeatureGateCheckController extends Controller
{
    public function __invoke(Request $request, FeatureGate $gate): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        if ($org === null) {
            return response()->json(['error' => 'No active organization.'], 422);
        }

        $fileSizeBytes = (int) $request->input('file_size_bytes', 0);
        $maxBytes = $gate->maxFileSizeBytes($org);

        if ($fileSizeBytes > $maxBytes) {
            $maxMb = round($maxBytes / 1024 / 1024);

            return response()->json([
                'error' => "File size exceeds your plan limit of {$maxMb} MB. Upgrade your plan.",
                'code' => 'file_too_large',
                'max_bytes' => $maxBytes,
            ], 422);
        }

        if (! $gate->canStartQaRunToday($org)) {
            $max = $gate->maxDailyQaRuns($org);

            return response()->json([
                'error' => "Daily QA run limit of {$max} reached. Upgrade your plan.",
                'code' => 'daily_limit_reached',
                'max_daily_qa_runs' => $max,
            ], 429);
        }

        return response()->json([
            'ok' => true,
            'plan' => $gate->planSlug($org),
            'max_daily_qa_runs' => $gate->maxDailyQaRuns($org),
            'max_file_size_bytes' => $maxBytes,
        ]);
    }
}
