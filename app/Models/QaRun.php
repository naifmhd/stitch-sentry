<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaRun extends Model
{
    /** @use HasFactory<\Database\Factories\QaRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'design_file_id',
        'preset',
        'status',
        'stage',
        'progress',
        'score',
        'risk_level',
        'error_code',
        'support_id',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'score' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<DesignFile, $this> */
    public function designFile(): BelongsTo
    {
        return $this->belongsTo(DesignFile::class);
    }

    /** @return HasMany<QaFinding, $this> */
    public function findings(): HasMany
    {
        return $this->hasMany(QaFinding::class);
    }

    /** @return HasMany<QaArtifact, $this> */
    public function artifacts(): HasMany
    {
        return $this->hasMany(QaArtifact::class);
    }
}
