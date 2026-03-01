<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Paddle\Billable;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use Billable, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'plan_slug',
    ];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<DesignFile, $this> */
    public function designFiles(): HasMany
    {
        return $this->hasMany(DesignFile::class);
    }

    /** @return HasMany<CreditsLedgerEntry, $this> */
    public function creditsLedgerEntries(): HasMany
    {
        return $this->hasMany(CreditsLedgerEntry::class);
    }

    /**
     * The display name sent to Paddle (uses the org name).
     */
    public function paddleName(): ?string
    {
        return $this->name;
    }

    /**
     * The email sent to Paddle — delegates to the first member of the org.
     */
    public function paddleEmail(): ?string
    {
        return $this->users()->first()?->email;
    }
}
