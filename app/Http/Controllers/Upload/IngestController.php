<?php

namespace App\Http\Controllers\Upload;

use App\Domain\Billing\Services\FeatureGate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\IngestRequest;
use App\Models\DesignFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class IngestController extends Controller
{
    public function __construct(private readonly FeatureGate $featureGate) {}

    /**
     * Handle an authenticated file upload — validate, store to S3, and record.
     */
    public function __invoke(IngestRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $org = $user->currentOrganization();

        if (! $org || ! $user->isMemberOfOrganization($org)) {
            return back()->withErrors(['file' => 'No organization context. Please select or create an organization first.']);
        }

        if (! $this->featureGate->canStartQaRunToday($org)) {
            return back()->withErrors([
                'file' => sprintf(
                    'You have reached the daily QA run limit (%d) for your current plan.',
                    $this->featureGate->maxDailyQaRuns($org)
                ),
            ]);
        }

        /** @var \Illuminate\Http\UploadedFile $uploadedFile */
        $uploadedFile = $request->file('file');

        $checksum = hash_file('sha256', $uploadedFile->getRealPath());
        $ext = strtolower((string) $uploadedFile->getClientOriginalExtension());
        $now = now();

        $storagePath = sprintf(
            'organizations/%d/uploads/%s/%s/%s.%s',
            $org->id,
            $now->format('Y'),
            $now->format('m'),
            $checksum,
            $ext
        );

        Storage::disk('s3')->putFileAs(
            dirname($storagePath),
            $uploadedFile,
            basename($storagePath)
        );

        $designFile = DesignFile::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'ext' => $ext,
            'size_bytes' => $uploadedFile->getSize(),
            'checksum' => $checksum,
            'storage_path' => $storagePath,
            'status' => 'uploaded',
        ]);

        // TODO (ISSUE 2.3): dispatch CreateQaRunJob($designFile) here.

        return to_route('upload.index')->with([
            'uploadSuccess' => true,
            'designFileId' => $designFile->id,
        ]);
    }
}
