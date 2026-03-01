<?php

namespace App\Domain\Billing\Services;

use App\Models\CreditsLedgerEntry;
use App\Models\Organization;
use DomainException;
use InvalidArgumentException;

class CreditsService
{
    /**
     * Compute the current credit balance for the given organization.
     */
    public function balance(Organization $org): int
    {
        return (int) CreditsLedgerEntry::query()
            ->where('organization_id', $org->id)
            ->sum('amount');
    }

    /**
     * Credit an organization's account.
     */
    public function credit(
        Organization $org,
        int $amount,
        string $reason,
        array $meta = [],
        ?string $idempotencyKey = null,
    ): CreditsLedgerEntry {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be greater than zero.');
        }

        $idempotencyKey ??= (string) str()->uuid();

        $existing = CreditsLedgerEntry::query()
            ->where('organization_id', $org->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return CreditsLedgerEntry::create([
            'organization_id' => $org->id,
            'amount' => $amount,
            'reason' => $reason,
            'meta_json' => $meta ?: null,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Debit an organization's account.
     *
     * Stores a negative amount. Idempotent when an idempotency key is provided.
     *
     * @throws InvalidArgumentException when amount is not positive.
     * @throws DomainException when the debit would make the balance negative.
     */
    public function debit(
        Organization $org,
        int $amount,
        string $reason,
        array $meta = [],
        ?string $idempotencyKey = null,
    ): CreditsLedgerEntry {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be greater than zero.');
        }

        $idempotencyKey ??= (string) str()->uuid();

        $existing = CreditsLedgerEntry::query()
            ->where('organization_id', $org->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $currentBalance = $this->balance($org);

        if ($currentBalance - $amount < 0) {
            throw new DomainException('Insufficient credits: debit would exceed available balance.');
        }

        return CreditsLedgerEntry::create([
            'organization_id' => $org->id,
            'amount' => -$amount,
            'reason' => $reason,
            'meta_json' => $meta ?: null,
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
