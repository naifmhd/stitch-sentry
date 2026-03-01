<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Services\CreditsService;
use App\Http\Controllers\Controller;
use App\Models\CreditsLedgerEntry;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class CreditsController extends Controller
{
    public function __construct(private readonly CreditsService $creditsService)
    {
    }

    /**
     * Display the credits ledger for the current organization.
     */
    public function __invoke(Organization $organization): Response
    {
        $entries = CreditsLedgerEntry::query()
            ->where('organization_id', $organization->id)
            ->latest()
            ->get()
            ->map(fn (CreditsLedgerEntry $entry) => [
                'id' => $entry->id,
                'amount' => $entry->amount,
                'reason' => $entry->reason,
                'meta_json' => $entry->meta_json,
                'created_at' => $entry->created_at,
            ]);

        return Inertia::render('billing/Credits', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'balance' => $this->creditsService->balance($organization),
            'entries' => $entries,
        ]);
    }
}
