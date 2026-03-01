<?php

namespace App\Http\Controllers\Upload;

use App\Domain\Billing\Services\FeatureGate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function __construct(private readonly FeatureGate $featureGate)
    {
    }

    /**
     * Display the file upload page.
     */
    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        $maxFileSizeMb = $org
            ? (int) round($this->featureGate->maxFileSizeBytes($org) / 1024 / 1024)
            : 10;

        return Inertia::render('upload/Index', [
            'maxFileSizeMb' => $maxFileSizeMb,
        ]);
    }
}
